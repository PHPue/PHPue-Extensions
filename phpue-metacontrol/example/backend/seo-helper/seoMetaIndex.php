<?php
    // Author: Edward Patch
    // This class is auto-loaded!
    // Learn how to make your own, by
    // exploring this example class!
    // (This file is not for production use - only for demonstration purposes.)
    class PHPueIndexMetaExample {
        private static ?self $instance = null;

        private string $title = '';

        private \PHPueExt\MetaControl $metaControl;

        private function __construct() {
            $this->metaControl = \PHPueExt\MetaControl::getInstance();


            if(!empty($this->metaControl->getMetaVariable('page-title'))) {
                $this->title = "<title>".$this->metaControl->getMetaVariable('page-title')."</title>";
                $this->metaControl->setRendered(true);
            } else {
                $this->title = "<title>Initial SSR Title</title>";
            }

            // if user navigates away, or opens new page, or refreshes
            if($this->metaControl->getRendered()) {
                $this->metaControl->unsetMetaVariables();
            }
        }

        public static function getInstance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function getTitle(): string {
            return $this->title;
        }
    }
?>