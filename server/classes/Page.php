<?php

/**
 * A Page is a dynamically-created object; it has no storage. It resizes and 
 * displays raw HTML using the 'page-view.php' and 'page-links.php' templates.
 * It only declares to HTTP methods: GET, for retrieving the page, and OPTIONS,
 * for retrieving an ordered HTML list of the links on the page.
 */
class Page extends ResourceGeneric {

    protected $cacheable = false;

    /**
     * Page number
     * @var int 
     */
    public $page;

    /**
     * Magazine ID
     * @var string 
     */
    public $magazine_id;

    /**
     * List of links on this page
     * @var array 
     */
    public $links = array(); // list of links on this page

    /**
     * URL to the JPEG for this page
     * @var string 
     */
    public $image;

    /**
     * Width of the page; correct aspect ratios are maintained using the methods
     * getCorrespondingHeight() and getCorrespondingWidth()
     * @var type 
     */
    public $width;

    /**
     * Height of the page; correct aspect ratios are maintained using the methods
     * getCorrespondingHeight() and getCorrespondingWidth()
     * @var type 
     */
    public $height;

    /**
     * If no width is set, this is the default width to display 
     */

    const DEFAULT_WIDTH = 800;

    /**
     * Save JPEGs with this quality; ranges from 0 to 100 
     */
    const JPEG_QUALITY = 95;

    /**
     * Constructor; set page number and magazine ID using either the constructor
     * parameters or URL tokens in the form: .../[resource]/[magazine_id]/[page]
     * @throws Error
     */
    public function __construct($magazine_id = null, $page = null) {
        // get URL tokens; pattern: /[resource]/[magazine_id]/[page]/...
        try {
            $tokens = WebUrl::getTokens();
        } catch (Error $e) {
            
        }
        // get magazine ID
        if ($magazine_id !== null) {
            $this->magazine_id = $magazine_id;
        } else {
            $this->magazine_id = @$tokens[1];
        }
        // get page
        if ($page !== null) {
            $this->page = $page;
        } else {
            $this->page = @$tokens[2];
        }
        // validate
        if (!$this->magazine_id) {
            throw new Error('No magazine ID set in url: .../[resource]/[magazine_id]/[page]/', 400);
        }
        if (!$this->page) {
            throw new Error('No page set in url: .../[resource]/[magazine_id]/[page]/', 400);
        }
    }

    /**
     * Get resource URI
     * @return string
     */
    public function getURI() {
        return 'page/' . $this->magazine_id . '/' . $this->page;
    }

    /**
     * Use 'page-view.php' as the template for this resource
     * @param type $representation
     * @return type
     */
    public function GET_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('server/ui/page-view.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->setVariable('data', $representation->getData());
        }
        return $representation;
    }

    /**
     * Use 'page-view.php' as the template for this resource
     * @param type $representation
     * @return type
     */
    public function OPTIONS_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('server/ui/page-links.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->setVariable('data', $representation->getData());
        }
        return $representation;
    }

    /**
     * GETs a page from a magazine
     * @return Page
     */
    public function GET() {
        // get resolution
        $width = (int) WebHttp::getParameter('width');
        $height = (int) WebHttp::getParameter('height');
        // case: both width and height specified
        if ($width && $height) {
            throw new Error('Specify only one dimension (width or height) when retrieving a page.', 400);
        }
        // case: neither specified
        if (!$width && !$height) {
            $width = self::DEFAULT_WIDTH;
        }
        // other cases: resizeJPEG() will determine corresponding width/height if only one dimension is set
        // validate resolution; only allow multiples of 50
        if ($width) {
            if ($width < 50 || $width % 50 !== 0) {
                throw new Error("Width parameter must be a multiple of 50 and greater than 0.", 400);
            }
        } elseif ($height) {
            if ($height < 50 || $height % 50 !== 0) {
                throw new Error("Height parameter must be a multiple of 50 and greater than 0.", 400);
            }
        }
        // resize image
        list($this->width, $this->height) = self::resizeJPEG($this->magazine_id, $this->page, $width, $height);
        // add image URL
        $this->image = self::getUrltoImage($this->magazine_id, $this->page, $width, $height);
        // add page links
        $_link = new Link();
        foreach ($_link->getStorage()->search('magazine_id', $this->magazine_id) as $key => $value) {
            if ($value->page == $this->page) {
                $this->links[] = $value;
            }
        }
        // return
        return $this;
    }

    /**
     * Return a list of links for this page
     * @return array of Links
     */
    public function OPTIONS() {
        // add page links
        $_link = new Link();
        foreach ($_link->getStorage()->search('magazine_id', $this->magazine_id) as $key => $value) {
            if ($value->page == $this->page) {
                $this->links[] = $value;
            }
        }
        // return
        return $this->links;
    }

    /**
     * @TODO test
     * @param type $id
     * @param type $max_width
     * @throws Error
     */
    public static function resizeJPEG($id, $page, $width = null, $height = null) {
        // test for GD
        if (!function_exists('gd_info')) {
            throw new Error('GD library must be enabled for image resizing to work.', 500);
        }
        // test for base image
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error('Missing file: ' . $path, 404);
        }
        // test if already created; getPathToImage() will handle logic regarding width/height/base dimensions
        $resized_path = self::getPathToImage($id, $page, $width, $height);
        if (file_exists($resized_path)) {
            list($width, $height, ) = getimagesize($resized_path);
            return array($width, $height);
        }
        // otherwise, create it; find width/height
        if ($width) {
            $height = self::getCorrespondingHeight($id, $page, $width);
        } elseif ($height) {
            $width = self::getCorrespondingWidth($id, $page, $height);
        } else {
            throw new Error('Execution should not reach this point.', 500);
        }
        // resize
        list($base_width, $base_height, ) = getimagesize($path);
        $source = imagecreatefromjpeg($path);
        $destination = imagecreatetruecolor($width, $height);
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $base_width, $base_height);
        // save
        imagejpeg($destination, $resized_path, self::JPEG_QUALITY);
        // return
        return array($width, $height);
    }

    /**
     * Return URL to page/image
     * @param string $id
     * @param int $page
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function getUrltoImage($id, $page, $width = null, $height = null) {
        $url = WebUrl::getDirectoryUrl() . 'server' . DS . 'data' . DS . $id;
        // add page
        $url .= '[' . $page . ']';
        // add width/height
        if ($width) {
            $url .= $width . 'w';
        } elseif ($height) {
            $url .= $height . 'h';
        }
        // finish and return
        $url .= '.jpg';
        return $url;
    }

    /**
     * Return path to page/image.
     * @param string $id
     * @param int $page
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function getPathToImage($id, $page, $width = null, $height = null) {
        $path = Magazine::getPathToData() . DS . $id;
        // add page
        $path .= '[' . $page . ']';
        // add width/height
        if ($width) {
            $path .= $width . 'w';
        } elseif ($height) {
            $path .= $height . 'h';
        }
        // finish
        $path .= '.jpg';
        return $path;
    }

    /**
     * Return the corresponding height for a given width so that
     * the height-width ratio is preserved.
     * @param string $id
     * @param int $page
     * @param int $width
     * @return int
     */
    public static function getCorrespondingHeight($id, $page, $width) {
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error('Missing file: ' . $path, 404);
        }
        // calculate ratio
        list($current_width, $current_height, ) = getimagesize($path);
        $ratio = $current_width / $current_height;
        $height = (int) $width / $ratio;
        // return
        return $height;
    }

    /**
     * Return the corresponding width for a given height so that
     * the height-width ratio is preserved.
     * @param string $id
     * @param int $page
     * @param int $height
     * @return int
     */
    public static function getCorrespondingWidth($id, $page, $height) {
        $path = self::getPathToImage($id, $page);
        if (!file_exists($path)) {
            throw new Error('Missing file: ' . $path, 404);
        }
        // calculate ratio
        list($current_width, $current_height, ) = getimagesize($path);
        $ratio = $current_width / $current_height;
        $width = (int) $height * $ratio;
        // return
        return $width;
    }

}