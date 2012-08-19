<?php

/**
 * A generic resource for storing the 'Follow Us' links required by the 
 * project specifications. Add more properties as needed.
 */
class Social extends ResourceGeneric {

    protected $cacheable = false;
    protected $storage = array('type' => 'json', 'location' => 'server/social.json');
    protected $saved = false;

    /**
     * Link to follow on Facebook
     * @var string 
     */
    public $facebook;

    /**
     * Link to follow on Pinterest
     * @var string 
     */
    public $pinterest;

    /**
     * Link to follow on Twitter
     * @var string 
     */
    public $twitter;

    /**
     * Use 'page-view.php' as the template for this resource
     * @param type $representation
     * @return type
     */
    public function PUT_OUTPUT_TRIGGER($representation) {
        if ($representation->getContentType() == 'text/html') {
            $representation->setTemplate('server/ui/admin-template.php', WebTemplate::PHP_FILE);
            $representation->getTemplate()->replace('title', $this->getURI());
            if ($this->saved) {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/social-edited.php', array('data' => $representation->getData()));
            } else {
                $representation->getTemplate()->replaceFromPHPFile('content', 'server/ui/social-edit.php', array('data' => $representation->getData()));
            }
        }
        return $representation;
    }

    /**
     * Update the social configuration; this method is also used to create the
     * initial record
     * @param stdClass $entity
     * @return Social
     */
    public function PUT($entity = null) {
        if ($entity == null) {
            $this->bind($this->getStorage()->first());
            return $this;
        }
        // validate
        BasicValidation::with($entity)
                ->isObject()
                ->withOptionalProperty('facebook')->isString()->hasLengthUnder(1000)
                ->upAll()
                ->withOptionalProperty('twitter')->isString()->hasLengthUnder(1000)
                ->upAll()
                ->withOptionalProperty('pinterest')->isString()->hasLengthUnder(1000);
        // bind
        $this->bind($entity);
        // save; uses a key for storage methods that require one
        if ($this->getStorage()->exists('SOCIAL-KEY')) {
            $this->getStorage()->update($entity, 'SOCIAL-KEY');
        } else {
            $this->getStorage()->create($entity, 'SOCIAL-KEY');
        }
        $this->changed();
        $this->saved = true;
        // return
        return $this;
    }

    /**
     * Retrieve the social settings
     * @return Social
     */
    public function GET() {
        // only one record stored in this store
        $this->bind($this->getStorage()->first());
        // return
        return $this;
    }

}