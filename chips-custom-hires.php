<?php
/*
Plugin Name: CHIPS Custom Image Uploader
Description: A plugin to upload images to a specific directory without adding them to the media library.
Version: 1.2
Author: Dan Shields
*/



/* SETTINGS PAGE DEFINITION */
function chips_custom_hires_add_admin_menu() {
    add_management_page('CHIPS High-resolution uploader', 'CHIPS High-resolution uploader', 'manage_options', 'custom_image_uploader', 'ciu_settings_page');
}
add_action('admin_menu', 'chips_custom_hires_add_admin_menu');

function chips_hires_enqueue_scripts() {
	$inSub = null;
	if (isset($_GET['subdir'])) {
		$inSub = sanitize_text_field($_GET['subdir']);
	}
	
	wp_enqueue_script('chips-hires-ajax-script', plugin_dir_url(__FILE__) . 'chips-cui-ajax.js', array(), null, true);
	wp_enqueue_script('plupload-all');
    wp_enqueue_style('wp-media-uploader');
	wp_localize_script('plupload-all', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
		'subdir' => $inSub,
        'nonce' => wp_create_nonce('file-uploader-nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'chips_hires_enqueue_scripts');

function ciu_settings_page() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
    ?>
	<style>
		.chips_cui_wrap {
			max-width: 800px;
			margin: 0 auto;
		}
		.chips_cui_wrap form {
			margin:50px 0;
			background:white;
			padding:20px;
		}
		.chips-upload-alert {
			/* max-width:800px; */
			margin:20px 0 20px 160px;
			padding:50px;
			border:none;
			text-align:center;
			font-size:24px;
			color:red;
		}
		form h3 {
			margin-top:0;
		}
		.picklist label { margin-right:2em; }
		.picklist legend { display:none; }
		.picklist label input[type="radio"]:not(:checked) ~ input[type="text"] { display:none; }
		form hr {
			margin-top:2em;
			height:2px;
			background:#999;
			border:none;
			opacity:0.5;
		}
		.custom-wrapper {
			width:100%;
			margin:15px 0;
		}
		.file-single { position:relative; display:block;background:white;padding:0.5em; margin-bottom:0.5em; cursor:pointer;font-size:12px; border:solid 2px transparent; }
		.file-single:hover { border-color:#999; }
		.subdir-single { display:inline-block; margin-right:0.5em; border:solid 2px #999; padding:0.5em; text-decoration: none; color:inherit; }
		.subdir-single.active { border-color:#000; }
		input[type="text"].custom-wrapper { padding:0.5em; border:solid 2px #999; appearance: none; line-height: 1; border-radius:0; }
		.copied:BEFORE {
			content:"Copied!";
			position:absolute;
			top:50%;
			right:100%;
			margin-right:20px;
			font-size:10px;
			text-transform:uppercase;
			animation:fadeCopied 1s 1s forwards 1;
			transform:translateY(-50%);
		}
		@keyframes fadeCopied {
			from { opacity:1; }
			to { opacity:0; }
		}
	</style>
	<script>
		function copyToClipboard(text, el) {
			el.classList.add('copied');

			var dummy = document.createElement("textarea");
			document.body.appendChild(dummy);
			dummy.value = text;
			dummy.select();
			document.execCommand("copy");
			document.body.removeChild(dummy);
		}
	</script>
    <div class="chips_cui_wrap">
    <h2>CHIPS high-resolution image uploader</h2>
	<p style="display:block;max-width:70ch;word-wrap:pretty;">These files do not go into the media library, and are not accessible to other Wordpress pages. This uploader should be used only for image uploads that require a high-resolution URL, such as image zooms.</p>

	<?php $settingsPageURL = get_site_url() . '/wp-admin/tools.php'; ?>
	<form action="<?php echo $settingsPageURL; ?>" method="get">
		<input type="hidden" name="page" value="custom_image_uploader">
		<h3>Upload directory</h3>
		<fieldset class="picklist">
        	<legend>Upload directory</legend>
        	<label>
        		<input type="radio" name="subdir-radio-picklist" value="" <?php 
					if (!isset($_GET['subdir'])) {
						echo 'checked';
					}
				?>>
        		<span class="rich-label">
        			<b class="rich-label--title">Default</b>
        		</span>
			</label>
			<label class="has-custom-input">
				<input type="radio" name="subdir-radio-picklist" value="custom" <?php
					if (isset($_GET['subdir'])) {
						echo 'checked';
					}
				?>>
				<span class="rich-label">
					<b class="rich-label--title">Custom</b>
				</span>
				<input name="subdir" required class="custom-wrapper" value="<?php 
					if(isset($_GET['subdir'])){
						echo $_GET['subdir'];
					} ?>" type="text" placeholder="Enter directory and press enter">
			</label>
        </fieldset>
		<?php 
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'];
			$upload_dir = $upload_dir . '/img-hires';
			// find all subdirectories in the $upload_dir
			$subdirs = array_diff(scandir($upload_dir, SCANDIR_SORT_DESCENDING), array('.', '..'));
			$subdirs = array_filter($subdirs, function($item) use ($upload_dir) {
				return is_dir($upload_dir . '/' . $item);
			});
			$subdirs = array_values($subdirs);
			if(!empty($subdirs)){
				$baseURL = get_site_url() . '/wp-admin/tools.php';
				echo '<hr><h4>Previously-created directories</h4>';
				echo '<a class="subdir-single';
				if (!isset($_GET['subdir'])) {
					echo ' active';
				}
				echo '" href="' . $baseURL . '?page=custom_image_uploader">Default</a> ';
				foreach($subdirs as $subdir) {
					?>
					<a class="subdir-single<?php 
						if (isset($_GET['subdir']) && $_GET['subdir'] == $subdir) {
							echo ' active';
						}
					?>" href="<?php 
						echo $baseURL . '?page=custom_image_uploader&subdir=' . $subdir;
					?>"><?php echo $subdir; ?></a>
					<?php
				}
			}
		?>
		
	</form>
	<form id="ciu_image-form">
		<h3>File(s)</h3>
    	<input id="ciu_image" type="file" name="file" multiple="true">
		<?php if(isset($_GET['subdir'])) { ?>
			<input type="hidden" name="subdir" value="<?php echo $_GET['subdir'] ?>">
		<?php } ?>
    	<div id="ciu_image-progress"></div>
    </form>

	<?php 
	$subdir = null;
	if (isset($_GET['subdir'])) {
		$subdir = sanitize_text_field($_GET['subdir']);
	}
	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];
	$upload_dir = $upload_dir . '/img-hires';
	if($subdir) {
		$upload_dir = $upload_dir . '/' . $subdir;
	}
	
	if (!file_exists($upload_dir)) {
		return; // if the directory doesn't exist, exit
	}
	$files = array_diff(scandir($upload_dir, SCANDIR_SORT_DESCENDING), array('.', '..'));
	$files = preg_grep('/\.(jpg|jpeg|gif|png)$/i', $files);
	
	$files = array_diff($files, array('.', '..'));
	// $files = array_slice($files, 0, 10); // limit to 10 files
	?>
	<h3>Recent uploads:</h3>
	<?php 
		if (!empty($files)) {
			foreach ($files as $file) {
				?>
				<span class="file-single" onclick="copyToClipboard('<?php 
					$upload_dir = wp_upload_dir();
					$upload_dir = $upload_dir['baseurl'];
					$upload_dir = $upload_dir . '/img-hires';
					if (isset($_GET['subdir'])) {
						$upload_dir = $upload_dir . '/' . $_GET['subdir'];
						$inSub = true;
						// echo $upload_dir . '/' . sanitize_text_field($_GET['subdir']) . '/';
					// } else {
					}
					
					
					echo $upload_dir . '/';
					echo $file;
				?>',this)"><?php echo $file; ?></span>
				<?php
			}
		}
	?>

    </div>
    <?php
}

function ciu_handle_upload() {
	$uploadedfile = $_FILES['file'];
	$upload_overrides = array('test_form' => false);

	$subdir = null;
	if (isset($_POST['subdir'])) {
		$subdir = sanitize_text_field($_POST['subdir']);
	}

	$allowed = array('jpg', 'jpeg', 'gif', 'png');
	$ext = pathinfo($uploadedfile['name'], PATHINFO_EXTENSION);
	if (!in_array($ext, $allowed)) {
		echo "<div class=\"chips-upload-alert\">File type not allowed. Please upload a valid image.</div>";
		return;
	}

	$movefile = wp_handle_upload($uploadedfile, $upload_overrides);

	if ($movefile && !isset($movefile['error'])) {
		echo "<div class=\"chips-upload-alert\">File is valid, and was successfully uploaded.</div>\n";

		$filename = $movefile['file'];
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		if (!file_exists($upload_dir . '/img-hires')) {
			mkdir($upload_dir . '/img-hires'); // create the directory if it doesn't exist
		}
		
		$upload_dir = $upload_dir . '/img-hires';
		
		if($subdir) {
			$upload_dir = $upload_dir . '/' . $subdir;
			if (!file_exists($upload_dir)) {
				mkdir($upload_dir); // create the subdirectory if it doesn't exist
			}
		}
		$upload_dir = $upload_dir . '/' . basename($filename);
		rename($filename, $upload_dir);

		echo 'Success: ' . $filename;

	} else {
		echo "<div class=\"chips-upload-alert\">".$movefile['error']."</div>";
	}

	die();
}
add_action('wp_ajax_handle_file_upload', 'ciu_handle_upload');
add_action('wp_ajax_nopriv_handle_file_upload', 'ciu_handle_upload');
