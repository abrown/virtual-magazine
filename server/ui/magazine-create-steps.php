<?php
// store a session variable to allow access to upload.php
session_save_path(get_base_dir() . DS . '..' . DS . 'session');
ini_set('session.gc_probability', 1);
WebSession::put('USER_CAN_UPLOAD', 1);
?>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::getSiteUrl(); ?>client/swfupload/swfupload.js"></script>
<script type="text/javascript">
    // test for flash
    var hasFlash = false;
    try {
        var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
        if(fo) hasFlash = true;
    }catch(e){
        if(navigator.mimeTypes ["application/x-shockwave-flash"] != undefined) hasFlash = true;
    }

    
    $(document).ready(function () {
        // if flash exists, do not display redundant input box
        if(!hasFlash){
            $('#local-file-name').hide();
        }
        
        // on page load, always reset the submit button to enabled
        $('#magazine-submit').removeAttr('disabled');
        
        // on page load, always reset the upload file text
        $('#local-file-name').val('');
        
        // if the form is submitted, first upload the queued files
        $('#magazine-create').submit(function(e){
            if(CURRENT_ID && SWFU.getFile(CURRENT_ID).filestatus == SWFUpload.FILE_STATUS.QUEUED){
                SWFU.startUpload();
                $('#magazine-submit').attr('disabled', 'disabled').val('Uploading file...');
                e.preventDefault();
            }
        });
        
        // set up SWFUpload
        var CURRENT_ID;
        var SWFU = new SWFUpload({ 
            upload_url : "<?php echo WebUrl::getSiteUrl(); ?>upload.php", 
            flash_url : "<?php echo WebUrl::getSiteUrl(); ?>client/swfupload/Flash/swfupload.swf",
            post_params: {"php_session_id": "<?php echo session_id(); ?>"},
            
            // file
            file_size_limit : "200 MB",
            file_queue_limit: 1,
            file_types : "*.pdf",
            file_types_description : "PDF Files",
            
            // button
            button_placeholder_id: "magazine-upload",
            button_image_url: "<?php echo WebUrl::getSiteUrl(); ?>site/images/upload.png", // required or a HTTP GET is fired
            button_text: "<span class='magazine-upload-button'>Browse...</span>",
            button_text_style: ".magazine-upload-button { color: black; font-family: Arial,Helvetica,sans-serif; font-size: 16px; }",
            button_width: "80",
            button_height: "20",
            button_text_top_padding: 3,
            
            // event handlers
            file_dialog_start_handler: function(){
                if(CURRENT_ID) this.cancelUpload(CURRENT_ID);
                $('#local-file-name').val('');
            },
            file_queued_handler: function(file){
                CURRENT_ID = file.id;
                $('#local-file-name').val(file.name);
            },
            upload_progress_handler: function (file, bytesLoaded, bytesTotal) {
                try {
                    var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
                    $('#magazine-upload-progress').html(percent + '%');
                } catch (ex) {
                    this.debug(ex);
                }
            },
            upload_error_handler: function(file, error, message){
                console.log(file);
                console.log(error);
                console.log(message);
                alert('Upload failed: '+message);
                CURRENT_ID = null;
            },
            upload_success_handler: function(file, server_data, received_response){
                console.log("success");
                console.log(file);
                console.log(server_data);
                console.log(received_response);
                CURRENT_ID = null;
                hasError = (server_data.match(/Error:/gi)) ? true : false;
                if(hasError){
                    alert('Upload failed with error: '+server_data);
                    $('#magazine-submit').removeAttribute('disabled').val('Upload');
                }
                else{
                    $('#magazine-uploaded-pdf').val(server_data);
                    $('#magazine-submit').val('Converting file...');
                    $('#magazine-create').submit();
                }
            }
        });
    });
</script>
<h2>Create Magazine</h2>
<p>
    To create a magazine, follow the steps below.
</p>

<!-- INFO -->
<h3>1. Save Magazine Information</h3>
<p>
    This forms an entry in the magazine library for the new magazine. Please note that the ID may only contain letters, numbers, hyphens, and underscores.
</p>
<form id="magazine-create" method="POST" action="<?php echo WebUrl::getLocationUrl() . '/magazine'; ?>" enctype="multipart/form-data">
    <table class="admin-table magazine">
        <tr>    
            <td>Title</td>
            <td id="magazine-title"><input type="text" name="title" value="" title="Enter the title to display for this magazine." required="required"/></td>
        </tr>
        <tr>    
            <td>Description</td>
            <td id="magazine-description"><input type="text" name="description" value="" title="Enter a description to display for this magazine." /></td>
        </tr>
        <tr>    
            <td>ID</td>
            <td id="magazine-id"><input type="text" name="id" value="" pattern="[A-Za-z0-9-_]+" title="Enter an alpha-numeric ID (hyphens and underscores are allowed) to identify this magazine." required="required"/></td>
        </tr>
        <tr>    
            <td>Tracking Code</td>
            <td id="magazine-tracking-code"><input type="text" name="tracking_code" value="" title="Enter the Google Analytics tracking code to associate with this magazine." /></td>
        </tr>
        <tr>    
            <td>PDF</td>
            <td id="magazine-pdf">
                <!-- SWFUpload -->
                <input type="hidden" id="magazine-uploaded-pdf" name="uploaded_pdf" />
                <input type="text" id="local-file-name" value="" title="File to upload." disabled="disabled" />
                <input id="magazine-upload" type="file" name="pdf" accept="application/pdf" title="Locate a PDF file for this magazine." required="required"/>
                <span id="magazine-upload-progress"></span>
            </td>
        </tr>
        <tr>    
            <td></td>
            <td><input type="submit" id="magazine-submit" value="Save"/></td>
        </tr>
    </table>
</form>

<!-- UPLOAD -->
<h3>2. Upload a PDF</h3>
<table class="admin-table magazine">
    <tr>    
        <td>PDF</td>
        <td id="magazine-pdf">
            <!-- SWFUpload -->
            <input type="hidden" id="magazine-uploaded-pdf" name="uploaded_pdf" />
            <input type="text" id="local-file-name" value="" title="File to upload." disabled="disabled" />
            <input id="magazine-upload" type="file" name="pdf" accept="application/pdf" title="Locate a PDF file for this magazine." required="required"/>
            <span id="magazine-upload-progress"></span>
        </td>
    </tr>
    <tr>    
        <td></td>
        <td><input type="submit" id="magazine-submit" value="Upload"/></td>
    </tr>
</table>

<!-- CONVERT -->
<h3>2. Conver the PDF</h3>
<table class="admin-table magazine">
    <tr>    
        <td>PDF</td>
        <td id="magazine-pdf">
            <!-- SWFUpload -->
            <input type="hidden" id="magazine-uploaded-pdf" name="uploaded_pdf" />
            <input type="text" id="local-file-name" value="" title="File to upload." disabled="disabled" />
            <input id="magazine-upload" type="file" name="pdf" accept="application/pdf" title="Locate a PDF file for this magazine." required="required"/>
            <span id="magazine-upload-progress"></span>
        </td>
    </tr>
    <tr>    
        <td></td>
        <td><input type="submit" id="magazine-submit" value="Upload"/></td>
    </tr>
</table>
