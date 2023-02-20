/*
 * Provide a message and an error status (is error = 2, is warning = 1, is fine = 0 (default)).
 * If the message is empty, the status field is cleared
 */
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

/*
 * Stage 2 - Check backend compatibility.
 *
 * Data is guaranteed to exist here, but is not guaranteed to be correct.
 */
function generateCodeStageTwo(theDomainString,theUsernameString,thePasswordString,theKeyPasswordString)
{

}
/*
 * Stage 1 - Check field validity
 */
function generateCodeStageOne(theDomainString,theUsernameString,thePasswordString,theKeyPasswordString)
{
	if (theDomainString)
	{
		if (theUsernameString)
		{
			if (thePasswordString)
			{
				if (theKeyPasswordString)
				{
					updateStatus("Working on it...");
					generateCodeStageTwo(theDomainString,theUsernameString,thePasswordString,theKeyPasswordString);
				}
				else
				{
					updateStatus("The key passphrase is invalid.");
				}
			}
			else
			{
				updateStatus("The password is invalid.");
			}
		}
		else
		{
			updateStatus("The username is invalid.");
		}
	}
	else
	{
		updateStatus("The domain is invalid.");
	}
}
/*
 * Assign hooks to the button, such that it calls the needed function that talks to the backend.
 */
function assignHooks()
{
	let theDomain = document.getElementById("theDomainSelector");
	let theUsername = document.getElementById("theUsername");
	let thePassword = document.getElementById("thePassword");
	let theKeyPassword = document.getElementById("theKeyPassword");
	let theGenerateButton = document.getElementById("theGenerateButton");

	theGenerateButton.onclick = function() {
		generateCodeStageOne(theDomain.value,theUsername.value,thePassword.value,theKeyPassword.value)
	};


}
function bindJavaScript()
{
	updateStatus();
	assignHooks();
}

