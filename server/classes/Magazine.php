<?php

/**
 * A magazine is a collection of JPEG images stored in 'server/data'. These images
 * are created using GhostScript and are resized on-the-fly with the Page class.
 */
class Magazine extends ResourceItem {

    protected $storage = array('type' => 'json', 'location' => 'server/library.json');
    protected $cacheable = false;

    /**
     * Magazine ID
     * @var string an alpha-numeric string that may also contain - and _
     */
    public $id;

    /**
     * Magazine title
     * @var string 
     */
    public $title;

    /**
     * Magazine description text
     * @var string 
     */
    public $description;

    /**
     * Google analytics tracking code for this magazine; the Google Analytics
     * scripts are loaded by 'magazine-view.php' and each page is tracked by 
     * a push in 'page-view.php' using this code.
     * @var string 
     */
    public $tracking_code;

    /**
     * Date the magazine was created; currently stored as a string in the format:
     * [3-letter month] [date], [year].
     * @var string 
     */
    public $created;

    /**
     * Server path to the PDF file for this magazine
     * @var type 
     */
    public $pdf;

    /**
     * A dynamic property created in GET; depends on WebUrl::getSiteUrl() so
     * if the 'server' directory is not top-level, this will be incorrect.
     * @var string 
     */
    public $url_to_pdf;

    /**
     * A dynamic property; lists the URLs of all pages in this Magazine
     * @var int 
     */
    public $pages;

    /**
     * Resolution to pass to GhostScript in DPI; we set this purposefully high
     * so that images will be clear when scaled down.
     */

    const RESOLUTION = 300;

    /**
     * Used by PUT/POST to determine what template to display for 'text/html'
     * @var boolean 
     */
    protected $saved = false;

    /**
     * Set templates for 'text/html' representations
     * @param Representation $representation
     * @return Representation
     */
    function OUTPUT_TRIGGER($representation) {
        /*
          if ($representation->getContentType() == 'multipart/form-data') {
          $representation->setContentType('text/html');
          } */
        // set main template
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('server/ui/admin-template.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->replace('title', $this->getURI());
        }
        return $representation;
    }

    /**
     * Set templates for 'text/html' representations
     * @param Representation $representation
     * @return Representation
     */
    function GET_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('server/ui/site-template.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->replace('title', $this->title);
            $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-view.php', array('data' => $representation->getData()));
        }
        return $representation;
    }

    /**
     * Set templates for 'text/html' representations
     * @param Representation $representation
     * @return Representation
     */
    function DELETE_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-deleted.php', array('data' => $representation->getData()));
        }
        return $representation;
    }

    /**
     * Set templates for 'text/html' representations
     * @param Representation $representation
     * @return Representation
     */
    function POST_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            if ($this->saved) {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-created.php', array('data' => $representation->getData()));
            } else {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-create.php', array('data' => $representation->getData()));
            }
        }
        return $representation;
    }

    /**
     * Set templates for 'text/html' representations
     * @param Representation $representation
     * @return Representation
     */
    function PUT_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            if ($this->saved) {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-edited.php', array('data' => $representation->getData()));
            } else {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/magazine-edit.php', array('data' => $representation->getData()));
            }
        }
        return $representation;
    }

    /**
     * GET a Magazine
     * @return Magazine
     */
    public function GET() {
        $this->bind($this->getStorage()->read($this->getID()));
        // add PDF URL
        $this->url_to_pdf = WebUrl::getSiteUrl() . 'server' . DS . 'data' . DS . $this->id . '.pdf';
        // add page URLs
        $this->pages = array();
        $number_of_pages = self::countPages($this->id);
        for ($i = 1; $i <= $number_of_pages; $i++) {
            $this->pages[] = WebUrl::create("page/{$this->id}/{$i}", false);
        }
        // return
        return $this;
    }

    /**
     * DELETE a magazine
     * @return Magazine
     */
    public function DELETE() {
        // update cache
        $number_of_pages = self::countPages($this->id);
        for ($i = 1; $i <= $number_of_pages; $i++) {
            $uri = "page/{$this->id}/{$i}";
            StorageCache::delete($uri);
        }
        StorageCache::markModified('library');
        // delete PDF
        @unlink(self::getPathToPdf($this->id));
        // delete JPEGs
        $search = self::getPathToData() . DS . $this->getID() . '*.jpg';
        $files = glob($search);
        foreach ($files as $file) {
            @unlink($file);
        }
        // delete links
        $_link = new Link();
        $_link->getStorage()->begin();
        foreach ($_link->getStorage()->search('magazine_id', $this->id) as $id => $value) {
            $_link->getStorage()->delete($id);
        }
        $_link->getStorage()->commit();
        // mark changed
        $this->changed(); // @TODO move marking all pages and links changed to this
        // return
        return parent::DELETE();
    }

    /**
     * Create a Magazine; POST with no input data returns
     * the 'magazine-create.php' template when using text/html;
     * otherwise, POST saves the uploaded PDF and uses 
     * GhostScript to create JPEGs for it.
     * @param stdClass $entity
     * @return string
     * @throws Error
     */
    public function POST($entity) {
        if ($entity == null) {
            return ''; // no data returned, go to 'magazine-create.php' template
        }
        // validate fields
        if( !@$entity->tracking_code ) unset($entity->tracking_code); // browsers will submit an empty string that triggers the regex validation
        BasicValidation::with($entity)
                ->isObject()
                ->withProperty('id')->isAlphanumeric()->isNotEmpty()->hasLengthUnder(20)
                ->upAll()
                ->withProperty('title')->isString()->isNotEmpty()->hasLengthUnder(100)
                ->upAll()
                ->withOptionalProperty('description')->isString()->hasLengthUnder(1000)
                ->upAll()
                ->withOptionalProperty('tracking_code')->matches('/^ua-\d{4,9}-\d{1,4}$/i');
        // check for prior existence
        BasicValidation::with(@$entity->id)->isAlphanumeric();
        if ($this->getStorage()->exists($entity->id)) {
            throw new Error("A magazine with ID '{$entity->id}' already exists.", 400);
        }
        // check mime type
        if ($entity->files->pdf->type != 'application/pdf') {
            throw new Error('Upload must be a PDF', 400);
        }
        // bind
        $this->id = $entity->id;
        $this->title = htmlentities(@$entity->title);
        $this->description = htmlentities(@$entity->description);
        $this->tracking_code = @$entity->tracking_code;
        $this->created = date('M j, Y');
        $this->pdf = $this->getPathToPdf($this->id);
        // create PDF
        $length = file_put_contents($this->pdf, $entity->files->pdf->contents);
        if ($length != $entity->files->pdf->size) {
            throw new Error('Failed while saving upload.', 400);
        }
        // create JPEGs
        $pages_created = $this->createJPEG($this->id);
        if ($pages_created < 1) {
            throw new Error('No pages created for ID: ' . $this->id, 400);
        }
        // create
        $id = $this->getStorage()->create($this, $this->getID());
        // mark as saved/changed
        $this->saved = true;
        $this->changed();
        // return
        return $id;
    }

    /**
     * Edit a magazine; 
     * @param stdClass $entity
     * @return Magazine 
     */
    public function PUT($entity = null) {
        if ($entity == null) {
            $this->GET();
            return $this; // no data returned, go to 'magazine-edit.php' template
        }
        // check fields
        if( !@$entity->tracking_code ) unset($entity->tracking_code); // browsers will submit an empty string that triggers the regex validation
        BasicValidation::with($entity)
                ->isObject()
                ->hasNoProperty('id')
                ->withProperty('title')->isString()->isNotEmpty()->hasLengthUnder(100)
                ->upAll()
                ->withOptionalProperty('description')->isString()->hasLengthUnder(1000)
                ->upAll()
                ->withOptionalProperty('tracking_code')->matches('/^ua-\d{4,9}-\d{1,4}$/i');
        // save
        $result = parent::PUT($entity);
        // mark as saved/changed
        $this->saved = true;
        $this->changed();
        // return
        return $result;
    }

    /**
     * Return the path to the 'data' folder and ensure that
     * it is accessible to the current user
     * @return string
     * @throws Error
     */
    public static function getPathToData() {
        $path = realpath(get_base_dir() . '/../data');
        if ($path == false) {
            throw new Error('Could not find data directory.', 404);
        }
        if (!is_writeable($path)) {
            throw new Error('Cannot write to: ' . $path, 500);
        }
        return $path;
    }

    /**
     * Return path to the PDF assigned to this ID
     * @param string $id
     * @return string
     */
    public static function getPathToPdf($id) {
        $path = self::getPathToData() . DS . $id . '.pdf';
        return $path;
    }

    /**
     * Return path to 'gs'; necessary because some environments
     * do not include the path to the 'gs' binary
     * @return type
     */
    public static function getPathToGhostscript() {
        $whereis = `whereis gs`;
        $start = strpos($whereis, ':');
        if ($start === false) {
            $start = 0;
        }
        $whereis = ltrim(substr($whereis, $start + 1));
        $end = strpos($whereis, ' ');
        if ($end !== false) {
            $whereis = substr($whereis, 0, $end);
        }
        $gs = trim($whereis);
        return $gs;
    }

    /**
     * Create JPEGs for a given ID; the ID must have a 
     * matching PDF file within the data folder or this
     * method throws an Error.
     * @param string $id
     * @return int number of pages created
     * @throws Error
     */
    public static function createJPEG($id) {
        $path = self::getPathToPdf($id);
        if (!file_exists($path)) {
            throw new Error('Could not find PDF file: ' . $path, 404);
        }
        $file_path = self::getPathToData() . DS . $id;
        $command = self::getPathToGhostscript() . " -dNOPAUSE -sDEVICE=jpeg -r" . self::RESOLUTION . " -sOutputFile={$file_path}[%d].jpg {$path}";
        exec($command, $output, $return_value);
        if ($return_value !== 0) {
            $error = implode("\n", $output);
            throw new Error('Ghostscript failure returned: ' . $error, 500);
        }
        // return
        return self::countPages($id);
    }

    /**
     * Count the number of JPEG pages built for a given
     * ID.
     * @param string $id
     * @return int
     */
    public static function countPages($id) {
        $search = self::getPathToData() . DS . $id . '\[*\].jpg';
        $files = glob($search);
        return (int) count($files);
    }

}
