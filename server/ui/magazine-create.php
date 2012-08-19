<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#magazine-create').submit(function(){
            $('#magazine-create #magazine-submit').attr('disabled', 'disabled').val('Uploading file...');
        });
    });
</script>
<h2>Create Magazine</h2>
<p>
    Upload a PDF to create a magazine. Please note that the ID may only contain letters, numbers, hyphens, and underscores.
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
            <td id="magazine-pdf"><input type="file" name="pdf" accept="application/pdf" title="Locate a PDF file for this magazine." required="required"/></td>
        </tr>
        <tr>    
            <td></td>
            <td><input type="submit" id="magazine-submit" value="Upload"/></td>
        </tr>
    </table>
</form>
