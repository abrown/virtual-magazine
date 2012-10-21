<?php
// store a session variable to allow access to upload.php
WebSession::put('USER_CAN_UPLOAD', 1);
// @TODO implement XHR2 uploader based on http://www.matlus.com/html5-file-upload-with-progress/
?>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::create('client/message.js'); ?>"></script>
<script type="text/javascript" src="<?php echo WebUrl::create('client/swfupload/swfupload.js'); ?>"></script>
<script type="text/javascript">
    // test for flash
    var hasFlash = false;
    try {
        var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
        if(fo) hasFlash = true;
    }catch(e){
        if(navigator.mimeTypes ["application/x-shockwave-flash"] != undefined) hasFlash = true;
    }

    /**
     * Start the SWFUpload object
     */
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
            upload_url : "<?php echo WebUrl::create('upload.php'); ?>", 
            flash_url : "<?php echo WebUrl::create('client/swfupload/Flash/swfupload.swf'); ?>",
            post_params: {"php_session_id": "<?php echo session_id(); ?>"},
            
            // file
            file_size_limit : "200 MB",
            file_queue_limit: 1,
            file_types : "*.pdf",
            file_types_description : "PDF Files",
            
            // button
            button_placeholder_id: "magazine-upload",
            button_image_url: "<?php echo WebUrl::create('site/images/upload.png') ?>", // required or a HTTP GET is fired
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
                alert('Upload failed: '+message);
                CURRENT_ID = null;
            },
            upload_success_handler: function(file, server_data, received_response){
                CURRENT_ID = null;
                var hasError = (server_data.match(/Error:/gi)) ? true : false;
                if(hasError){
                    alert('Upload failed with error: '+server_data);
                    $('#magazine-submit').removeAttribute('disabled').val('Create');
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
<form id="magazine-create" method="POST" action="<?php echo WebUrl::createAnchoredUrl('magazine'); ?>" enctype="multipart/form-data">
    <fieldset>  
        <legend>Data</legend>
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="" title="Enter the title to display for this magazine." required="required"/>
        <label for="description">Description</label>
        <input type="text" id="description" name="description" value="" title="Enter a description to display for this magazine." />
        <label for="id" title="Enter an alpha-numeric ID (hyphens and underscores are allowed) to identify this magazine."><span class="help">ID</span></label>
        <input type="text" id="id" name="id" value="" pattern="[A-Za-z0-9-_]+" title="Enter an alpha-numeric ID (hyphens and underscores are allowed) to identify this magazine." required="required"/>
        <label for="tracking_code" title="Enter the Google Analytics tracking code to associate with this magazine.">Google Analytics Tracking Code</label>
        <input type="text" id="tracking_code" name="tracking_code" value="" title="Enter the Google Analytics tracking code to associate with this magazine." />
    </fieldset>
    <fieldset>  
        <legend>File To Upload</legend>
        <label for="pdf">PDF</label>
        <!-- SWFUpload -->
        <input type="hidden" id="magazine-uploaded-pdf" name="uploaded_pdf" />
        <input type="text" id="local-file-name" value="" title="File to upload." disabled="disabled" />
        <input id="magazine-upload" type="file" name="pdf" accept="application/pdf" title="Locate a PDF file for this magazine." required="required"/>
        <span id="magazine-upload-progress"></span>
    </fieldset>
    <p>
        <input type="submit" id="magazine-submit" class="button" value="Create magazine"/>
    </p>
</form>