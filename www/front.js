/*
 * Provide a message and an error status (is error = 2, is warning = 1, is fine = 0 (default)).
 * If the message is empty, the status field is cleared
 */
let microsoftAuthConfigCache = null;

const otherUserStateKey = "wg_other_user_state";

function persistOtherUserState(isChecked,otherNameValue)
{
	try
	{
		const payload = {
			checked: !!isChecked,
			otherName: typeof otherNameValue === "string" ? otherNameValue : ""
		};
		sessionStorage.setItem(otherUserStateKey, JSON.stringify(payload));
	}
	catch (error)
	{
		// Ignored, persistence is best-effort only.
	}
}

function loadOtherUserState()
{
	try
	{
		const stored = sessionStorage.getItem(otherUserStateKey);
		if (stored)
		{
			const parsed = JSON.parse(stored);
			return {
				checked: !!parsed.checked,
				otherName: typeof parsed.otherName === "string" ? parsed.otherName : ""
			};
		}
	}
	catch (error)
	{
		// Ignore malformed storage data.
	}
	return null;
}

function updateStatus(theMessage,isError = 0)
{
	let statusElement = document.getElementById("responseField");
	if (theMessage !== undefined)
	{
		statusElement.textContent = theMessage;
	}
	else
	{
		statusElement.textContent = "";
	}
}

function fetchMicrosoftAuthConfig()
{
	if (microsoftAuthConfigCache)
	{
		return microsoftAuthConfigCache;
	}

	const request = new XMLHttpRequest();
	try
	{
		request.open("POST", "fetchms.php", false);
		request.send();
	}
	catch (error)
	{
		updateStatus("Unable to contact the authentication configuration endpoint.",2);
		return null;
	}

	if (request.status !== 200 || !request.responseText)
	{
		updateStatus("Server rejected authentication configuration request.",2);
		return null;
	}

	let parsedResponse;
	try
	{
		parsedResponse = JSON.parse(request.responseText);
	}
	catch (error)
	{
		updateStatus("Invalid configuration response from server.",2);
		return null;
	}

	const requiredFields = ["tenantId","clientId","scope","responseType","responseMode"];
	for (const field of requiredFields)
	{
		if (typeof parsedResponse[field] !== "string" || parsedResponse[field].length === 0)
		{
			updateStatus("The authentication configuration is incomplete.",2);
			return null;
		}
	}

	microsoftAuthConfigCache = parsedResponse;
	return parsedResponse;
}

function getRedirectUri()
{
	const {origin, pathname} = window.location;
	return origin + pathname;
}

function createOAuthState()
{
	if (window.crypto && window.crypto.getRandomValues)
	{
		const randomBuffer = new Uint32Array(4);
		window.crypto.getRandomValues(randomBuffer);
		return Array.from(randomBuffer, value => value.toString(16)).join("");
	}
	return (Date.now() + Math.random()).toString(36);
}

function buildMicrosoftAuthorizeUrl(state)
{
	const microsoftAuthConfig = fetchMicrosoftAuthConfig();
	if (!microsoftAuthConfig)
	{
		return null;
	}

	const redirectUri = encodeURIComponent(getRedirectUri());
	const scope = encodeURIComponent(microsoftAuthConfig.scope);
	return `https://login.microsoftonline.com/${microsoftAuthConfig.tenantId}/oauth2/v2.0/authorize?client_id=${microsoftAuthConfig.clientId}&response_type=${microsoftAuthConfig.responseType}&redirect_uri=${redirectUri}&response_mode=${microsoftAuthConfig.responseMode}&scope=${scope}&state=${state}`;
}

function redirectToMicrosoftLogin()
{
	const otherUserCheckbox = document.getElementById("generateForOther");
	const otherNameInput = document.getElementById("otherName");
	if (otherUserCheckbox && otherNameInput)
	{
		persistOtherUserState(otherUserCheckbox.checked,otherNameInput.value);
	}
	const state = createOAuthState();
	try
	{
		sessionStorage.setItem("ms_auth_state", state);
	}
	catch (error)
	{
		// Failing to persist state should not block the login redirect.
	}

	const authorizeUrl = buildMicrosoftAuthorizeUrl(state);
	if (!authorizeUrl)
	{
		return;
	}

	window.location.href = authorizeUrl;
}

function generateCodeStageThree(theConfigurationB64,fileLabel)
{
	if (!theConfigurationB64)
	{
		updateStatus("Server response missing configuration data.",2);
		return;
	}

	let decodedConfiguration;
	try
	{
		decodedConfiguration = atob(theConfigurationB64);
	}
	catch (error)
	{
		updateStatus("Unable to decode configuration payload.",2);
		return;
	}

	let downloadName = "wireguard.conf";
	if (fileLabel && typeof fileLabel === "string")
	{
		downloadName = fileLabel;
	}

	let triggerDownload = (function ()
	{
		var a = document.createElement("a");
		a.style = "display: none";
		document.body.appendChild(a);
		return function (data, fileName)
		{
			const blob = new Blob([data], {type: "octet/stream"});
			const url = window.URL.createObjectURL(blob);
			a.href = url;
			a.download = fileName;
			a.click();
			window.URL.revokeObjectURL(url);
		};
	}());

	triggerDownload(decodedConfiguration,downloadName);
}

function generateCodeStageTwo(theAuthorizationCode)
{
	if (!theAuthorizationCode)
	{
		updateStatus("Missing authorization code.",2);
		return;
	}

	let codeGenerationRequest = new XMLHttpRequest();
	codeGenerationRequest.onreadystatechange = function()
	{
		if (this.readyState === XMLHttpRequest.DONE)
		{
			if (this.status === 200)
			{
				let serverResponse;
				try
				{
					serverResponse = JSON.parse(codeGenerationRequest.responseText);
				}
				catch (error)
				{
					updateStatus("Unable to parse server response.",2);
					return;
				}

				let serverResponseCode = serverResponse.code;
				let serverResponseMessage = serverResponse.msg;

				if (serverResponseCode !== 0)
				{
					updateStatus(serverResponseMessage || "Generation failed.",serverResponseCode);
				}
				else
				{
					updateStatus("Generation complete!");
					let serverResponseDataB64 = serverResponse.data;
					let downloadLabel = "wireguard.conf";
					if (serverResponse.filename)
					{
						downloadLabel = serverResponse.filename;
					}
					else if (serverResponse.username)
					{
						downloadLabel = serverResponse.username + "_wireguard.conf";
					}
					generateCodeStageThree(serverResponseDataB64,downloadLabel);
				}
			}
			else
			{
				updateStatus("Server error while generating configuration.",2);
			}
		}
	};
	codeGenerationRequest.onerror = function()
	{
		updateStatus("Network error while contacting the server.",2);
	};
	codeGenerationRequest.open("POST", "index.php", true);
	codeGenerationRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	// ===== BUILD THE URL PARAMETERS =====
	const requestPayload = new URLSearchParams();
	requestPayload.append("code", theAuthorizationCode);
	const otherUserCheckbox = document.getElementById("generateForOther");
	const otherNameInput = document.getElementById("otherName");

	otherUserCheckboxChecked = otherUserCheckbox.checked;
	otherUserNameValue = otherNameInput.value;

	if (otherUserCheckboxChecked)
	{
		const sanitizedOtherName = (otherUserNameValue || "").trim();
		if (sanitizedOtherName.length > 0)
		{
			requestPayload.append("otherName", sanitizedOtherName);
		}
	}
	codeGenerationRequest.send(requestPayload.toString());
}

function handleAuthorizationRedirect()
{
	const params = new URLSearchParams(window.location.search);
	const authCode = params.get("code");
	if (!authCode)
	{
		return false;
	}

	const returnedState = params.get("state");
	let expectedState = null;
	try
	{
		expectedState = sessionStorage.getItem("ms_auth_state");
	}
	catch (error)
	{
		// Ignore storage errors, continue best-effort.
	}

	if (expectedState && returnedState && expectedState !== returnedState)
	{
		updateStatus("Authentication validation failed. Please try signing in again.",2);
		return true;
	}

	if (expectedState)
	{
		try
		{
			sessionStorage.removeItem("ms_auth_state");
		}
		catch (error)
		{
			// Ignore cleanup errors.
		}
	}

	updateStatus("Generating your Wireguard configuration...");
	generateCodeStageTwo(authCode);

	if (window.history && window.history.replaceState)
	{
		const cleanUrl = window.location.origin + window.location.pathname;
		window.history.replaceState({}, document.title, cleanUrl);
	}

	return true;
}

function assignHooks()
{
	let microsoftButton = document.getElementById("msLoginButton");
	if (microsoftButton)
	{
		microsoftButton.onclick = redirectToMicrosoftLogin;
	}

	const otherUserCheckbox = document.getElementById("generateForOther");
	const otherNameInput = document.getElementById("otherName");

	// ===== TOGGLE THE NAME BOX =====
	if (otherUserCheckbox && otherNameInput)
	{
		const savedState = loadOtherUserState();
		if (savedState)
		{
			otherUserCheckbox.checked = !!savedState.checked;
			otherNameInput.value = savedState.otherName || "";
		}

		const toggleOtherNameInput = function()
		{
			if (otherUserCheckbox.checked)
			{
				otherNameInput.style.display = "block";
				otherNameInput.focus();
			}
			else
			{
				otherNameInput.value = "";
				otherNameInput.style.display = "none";
			}
		};

		const syncOtherUserState = function()
		{
			persistOtherUserState(otherUserCheckbox.checked,otherNameInput.value);
		};

		otherUserCheckbox.addEventListener("change", () =>
		{
			toggleOtherNameInput();
			syncOtherUserState();
		});
		otherNameInput.addEventListener("input", syncOtherUserState);
		toggleOtherNameInput();
	}
}

function bindJavaScript()
{
	assignHooks();
	if (!handleAuthorizationRedirect())
	{
		updateStatus("Sign in with Microsoft to request your configuration.");
	}
}
