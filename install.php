<?php
declare(strict_types = 1);

/**
 * R2H Installer Plugin
 * @author    Michael Snoeren <michael@r2h.nl>
 * @copyright R2H Marketing & Internet Solutions © 2019
 * @license   GNU/GPLv3
 */

use Joomla\CMS\Factory;
use R2HInstaller\Version;
use R2HInstaller\Installer;
use R2HInstaller\Exceptions\InvalidVersionException;

defined('_JEXEC') or die;

class PlgSystemR2HInstallerInstallerScript
{
    /**
     * @var    Joomla\CMS\Application\SiteApplication $app The application object.
     * @access protected
     */
    protected $app;

    /**
     * @var    boolean $canInstall Determines if packages should be installed.
     * @access protected
     */
    protected $canInstall = true;

    /**
     * Installer preflight.
     * @access public
     * @return void
     */
    public function preflight()
    {
        $lang = Factory::getLanguage();
        $lang->load('plg_system_r2hinstaller');

        // Import required code.
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');
        JLoader::registerNamespace('R2HInstaller', __DIR__ . '/src/', false, false, 'psr4');

        $this->app = Factory::getApplication();
        try {
            Version::checkAll();
        } catch (InvalidVersionException $e) {
            Installer::uninstallInstaller();
            $this->app->enqueueMessage('R2H Installer: ' . $e->getMessage(), 'warning');

            $this->canInstall = false;
        }
    }

    /**
     * Installer postflight.
     * @param  string $route The name of the installer route.
     * @access public
     * @return boolean
     * @throws Exception Thrown when an installation step fails.
     */
    public function postflight(string $route)
    {
        if (!$this->canInstall) {
            return false;
        }

        $status = false;

        try {
            if (!in_array(strtolower($route), ['install', 'update'])) {
                throw new Exception('Invalid route.');
            }

            // Set the base path of all packages.
            if (!Installer::setPath(__DIR__ . '/packages/')) {
                throw new Exception('Failed to set base path.');
            }

            // Install all packages.
            if (!Installer::installPackages()) {
                throw new Exception('Failed to install all packages.');
            }

            $status = true;
        } catch (Exception $e) {
            $this->app->enqueueMessage('R2H Installer: ' . $e->getMessage(), 'warning');
        } finally {
            Installer::uninstallInstaller();
        }

        return $status;
    }
}
