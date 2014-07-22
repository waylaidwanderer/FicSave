var start;
var timer;
$("#download").click(function() {
	if (!$(this).hasClass("disabled"))
	{
		$(this).addClass("disabled");
		$.post("parser.php", $("#form").serialize())
		.done(function(data) {
			if (data == "done")
			{
				$(this).val("Waiting...");
				start = Date.now();
				timer = setInterval(checkDownload, 1000);
			}					
			else
			{
				$(this).val("Error!");
			}
		})
		.always(function() {
			$(this).val("Submitting...");
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
			window.location.replace("http://ficsave.com/process.php?get");
			$("#download").removeClass("disabled");
			$("#download").val("Download");
		}
		else
		{
			clearInterval(timer);
			$("#download").val("Error!");
		}
	});
}