<?php

   	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

    require_once "standardDefinitions.php";

    function fetchEmailAddress($theCode)
    {
        global $msTenantDetails;

        // Exchange authorization code for tokens
        $token_url = "https://login.microsoftonline.com/" . $msTenantDetails["tenantID"] . "/oauth2/v2.0/token";

        $post_fields = http_build_query(array(
            'client_id'     => $msTenantDetails["client_id"],
            'scope'         => $msTenantDetails["scope"],
            'redirect_uri'  => $msTenantDetails["redirect_uri"],
            'client_secret' => $msTenantDetails["client_secret"],
            'code'          => $theCode,
            'grant_type'    => 'authorization_code'
        ));

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response  = curl_exec($ch);
        $curl_err  = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        $toReturn = [];
        $toReturn[0] = false;
        $toReturn[1] = "Unknown error message.";

        if ($response === false)
        {
            $toReturn[1] = 'Token request failed: ' . htmlspecialchars($curl_err, ENT_QUOTES, 'ISO-8859-2');
        }
        else
        {
            $token_data = json_decode($response, true);
            if ($http_code !== 200 || !is_array($token_data) || !isset($token_data['id_token']))
            {
                $toReturn[1] = 'Authentication failed when exchanging the code for a token.';
            }
            else
            {
                // Decode ID token (JWT) to get basic user info (email)
                $id_token = $token_data['id_token'];
                $parts = explode('.', $id_token);

                if (count($parts) < 2)
                {
                    $toReturn[1] = 'Invalid ID token format.';
                }
                else
                {
                    $payload_json = base64_decode($parts[1]);
                    $payload = json_decode($payload_json, true);

                    if (!is_array($payload))
                    {
                        $toReturn[1] = 'Could not parse ID token payload.';
                    }
                    else
                    {
                        $email = null;
                        if (isset($payload['preferred_username']))
                        {
                            $toReturn[0] = true;
                            $toReturn[1] = $payload['preferred_username'];
                        }
                        else if (isset($payload['email']))
                        {
                            $toReturn[0] = true;
                            $toReturn[1] = $payload['email'];
                        }
                    }
                }
            }
        }


        return $toReturn;
    }