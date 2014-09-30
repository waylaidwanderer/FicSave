var start;
var timer;
var email;
var autoDownload;
$(document).ready(function()
{
	if ($("#email").val() != "")
	{
		$("#download").val("Send to Email");
	}

	autoDownload = GetURLParameter("auto_download") == "yes";
	if (autoDownload) {
		var storyUrl = $("#storyurl").val();
		if (storyUrl != "") {
			$("#download").trigger("click");
		}
	}
});

$("#bookmarkletbegin").click(function() {
	$("#bookmarkletbegin").fadeOut("fast");
	$("#bookmarkletform").fadeIn("medium");
});

$("#createbookmarklet").click(function() {
	var format = $("#bookmarkletformat").val();
	var formatName = $("#bookmarkletformat option:selected").text();
	var email = $("#bookmarkletemail").val();

	var bookmarkletFunctionString = "var storyUrl=window.location.href;var format=\""+format+"\";var email=\""+email+"\";var ficsaveUrl=\"http://ficsave.com/?story_url=\"+storyUrl+\"&format=\"+format+\"&email=\"+email+\"&auto_download=yes\";window.open(ficsaveUrl,\"_blank\");"

	var bookmarkletString = "javascript:" + encodeURI("(function(){"+bookmarkletFunctionString+"})()");	

	$("#bookmarkletlink").attr("href", bookmarkletString);
	$("#bookmarkletlink #bookmarkletformatstring").text(formatName);
	$("#bookmarklet-container").fadeIn("medium");
});

$("#download").click(function() {
	if (!$(this).hasClass("disabled"))
	{
		$(this).addClass("disabled");
		$.post("parser.php", $("#form").serialize())
		.always(function() {
			$(this).val("Submitting...");
		})
		.done(function(data) {
			if (data == "done")
			{
				$(this).val("Waiting...");
				email = $("#email").val();
				start = Date.now();
				timer = setInterval(checkDownload, 1000);				
			}					
			else
			{
				$("#download").val("Error!");
			}
		});	
	}
});

$('#email').keyup(function() {
	if ($(this).val() != "")
	{
		$("#download").val("Send to Email");
	}
	else
	{
		$("#download").val("Download");
	}
});

function checkDownload()
{
	$.get("process.php", function(data) {
		if (data == "waiting")
		{
			var seconds = Math.abs((Date.now() - start) / 1000);
			$("#download").val("Waiting. " + Math.floor(seconds) + "s...");
		}
		else if (data.indexOf("done") > -1)
		{
			clearInterval(timer);
			if (email === "")
			{
				var split = data.split("|");
				var format = split[1];
				var uniqid = split[2];				
				window.location.replace("http://ficsave.com/process.php?get=1&format=" + format + "&uniqid=" + uniqid);
				$("#download").val("Download");
			}
			else
			{
				$("#download").val("Email Sent!");
			}
			$("#download").removeClass("disabled");			
		}
		else
		{
			clearInterval(timer);
			$("#download").val("Error!");
		}
	});
}

function GetURLParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) 
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) 
        {
            return sParameterName[1];
        }
    }

    return "";
}