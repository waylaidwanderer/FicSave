var start;
var timer;
var email;
$(document).ready(function()
{
	if ($("#email").val() != "")
	{
		$("#download").val("Send to Email");
	}
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
		else if (data == "done")
		{
			clearInterval(timer);
			if (email === "")
			{
				window.location.replace("http://ficsave.com/process.php?get");
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