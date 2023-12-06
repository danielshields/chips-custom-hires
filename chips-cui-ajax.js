document.addEventListener("DOMContentLoaded", function () {
	var uploader = new plupload.Uploader({
		browse_button: "ciu_image",
		url: "admin-ajax.php",

		filters: {
			max_file_size: "20mb",
			mime_types: [
				{ title: "Image files", extensions: "jpg,jpeg,gif,png" },
			],
		},
		multipart_params: {
			action: "handle_file_upload",
			subdir: ajax_object.subdir,
			nonce: ajax_object.nonce,
		},
		init: {
			FilesAdded: function (up, files) {
				plupload.each(files, function (file) {
					document.getElementById("ciu_image-progress").innerHTML =
						document.getElementById("ciu_image-progress")
							.innerHTML +
						"<div id='" +
						file.id +
						"'>" +
						file.name +
						" (" +
						plupload.formatSize(file.size) +
						") <b></b></div>";
				});
				up.start();
			},
			UploadProgress: function (up, file) {
				if (file.percent == 100) {
					document.querySelector("#" + file.id + " b").innerHTML =
						" <span>Upload complete. <strong style='cursor:pointer;' onclick='window.location.reload()'>Refresh to copy the URL</strong></span>";
				} else {
					document.querySelector("#" + file.id + " b").innerHTML =
						"<span>" + file.percent + "%</span>";
				}
			},
			Error: function (up, err) {
				console.log(err);
				document
					.getElementById("ciu_image-progress")
					.appendChild(
						document.createTextNode(
							"\nError #" +
								err.code +
								": " +
								err.message +
								(err.file ? ", File: " + err.file.name : "")
						)
					);
			},
		},
	});
	uploader.init();
});
