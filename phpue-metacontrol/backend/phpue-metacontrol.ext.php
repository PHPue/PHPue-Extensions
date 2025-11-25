<?php
    /**
     * PHPue Extension: MetaControl
     *
     * A modern PHPue Extension for managing dynamic meta variables.
     *
     * @package    PHPueExt's MetaControl (Official)
     * @version    0.5.1
     * @author     Edward Patch
     * @license    PHPueExtensions Repo's Licence
     * @link       https://phpue.co.uk/
     * @copyright  2025 Edward Patch (Apache 2 License)
     * 
     * @description
     * This extension provides a singleton MetaControl class that allows
     * storing and retrieving meta variables (like page titles, descriptions,
     * or custom header data) for use throughout your PHPue-powered project.
     *
     * @usage
     * use PHPueExt\MetaControl;
     * 
     * MetaControl::getInstance()->setMetaVariable('page_title', 'Home');
     * echo MetaControl::getInstance()->getMetaVariable('page_title');
     */

    namespace PHPueExt;

    if (!defined('PHPUE_VERSION') || version_compare(PHPUE_VERSION, '0.0.1', '<')) {
        error_log("PHPue MetaControl: Incompatible framework version " . (PHPUE_VERSION ?? 'undefined'));
    }

    class MetaControl
    {
        private static ?self $instance = null;
        private array $META_MAP;
        private bool $MetaRendered = false;

        private function __construct() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $this->META_MAP = $_SESSION['PHPueExt_MetaMap'] ?? [];
            $this->MetaRendered = $_SESSION['PHPueExt_MetaRendered'] ?? false;
        }

        public static function getInstance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function setMetaVariable(string $key, mixed $value): void {
            $this->META_MAP[$key] = $value;
            $_SESSION['PHPueExt_MetaMap'] = $this->META_MAP; 
        }

        public function getMetaVariable(string $key): mixed {
            return $this->META_MAP[$key] ?? '';
        }

        public function unsetMetaVariables(): void {
            unset($_SESSION['PHPueExt_MetaMap']);
            $this->META_MAP = [];
        }

        public function setRendered(bool $rendered = true): void {
            $this->MetaRendered = $rendered;
            $_SESSION['PHPueExt_MetaRendered'] = $this->MetaRendered;
        }

        public function getRendered(): bool {
            return $this->MetaRendered;
        }
    }

    \PHPueExt\MetaControl::getInstance();
?>