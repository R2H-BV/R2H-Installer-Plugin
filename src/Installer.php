<?php
declare(strict_types = 1);

/**
 * R2H Installer Plugin
 * @author    Michael Snoeren <michael@r2h.nl>
 * @copyright R2H Marketing & Internet Solutions © 2019
 * @license   GNU/GPLv3
 */

namespace R2HInstaller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

abstract class Installer
{
    /**
     * @var    string $path The basepath to all packages.
     * @access protected
     * @static
     */
    protected static $path = '';

    /**
     * Set the path to all packages.
     * @param  string $path The path.
     * @access public
     * @return boolean
     * @throws \InvalidArgumentException Thrown on empty path.
     * @static
     */
    public static function setPath(string $path): bool
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Installation path is empty.');
        }

        // Set the path while making sure it has a trailing slash.
        self::$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return true;
    }

    /**
     * Install a package from the packages folder.
     * @param  string $name The folder name containing the package.
     * @access public
     * @return boolean
     * @throws \InvalidArgumentException Thrown on empty package name.
     * @static
     */
    public static function installPackage(string $name): bool
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Package name is empty.');
        }

        // Prepare data.
        $app = Factory::getApplication();
        $lang = Factory::getLanguage();
        $dir = self::$path . $name;

        // Check if the package exists.
        if (!\JFolder::exists($dir)) {
            $app->enqueueMessage(
                Text::sprintf(
                    'PLG_SYSTEM_R2HINSTALLER_ERROR_INSTALLER_FAILED',
                    $dir
                ),
                'error'
            );
            return false;
        }

        // Setup the installer.
        $installer = new \Joomla\CMS\Installer\Installer;
        $installer->setPath('source', $dir);

        // Get the manifest and check if its valid.
        $manifest = (array) $installer->getManifest();
        if (!count($manifest) || !array_key_exists('name', $manifest) || !array_key_exists('version', $manifest)) {
            $app->enqueueMessage(
                Text::sprintf(
                    'PLG_SYSTEM_R2HINSTALLER_ERROR_INSTALLER_INVALID',
                    $dir
                ),
                'error'
            );
            return false;
        }

        // Install the package.
        if (!(bool) $installer->install($dir)) {
            $app->enqueueMessage(
                Text::sprintf(
                    'PLG_SYSTEM_R2HINSTALLER_ERROR_INSTALLER_FAILED',
                    $dir
                ),
                'error'
            );
            return false;
        }

        // Show a message.
        $lang->load($manifest['name']);
        $app->enqueueMessage(
            Text::sprintf(
                'PLG_SYSTEM_R2HINSTALLER_ERROR_INSTALLER_INSTALLED',
                '<strong>' . Text::_($manifest['name']) . '</strong>',
                '<strong>v' . $manifest['version'] . '</strong>'
            ),
            'message'
        );

        return true;
    }

    /**
     * Install all packages from the basepath.
     * @access public
     * @return boolean
     * @throws \UnexpectedValueException Thrown when the base path is empty.
     * @static
     */
    public static function installPackages(): bool
    {
        if (empty(self::$path)) {
            throw new \UnexpectedValueException('Base path is empty.');
        }
        if (!\JFolder::exists(self::$path)) {
            throw new \UnexpectedValueException('Given packages folder does not exist.');
        }

        // Get and install all packages.
        $packages = \JFolder::folders(self::$path);
        foreach ($packages as $package) {
            self::installPackage($package);
        }

        return true;
    }

    /**
     * Uninstall the installer.
     * @access public
     * @return boolean
     * @static
     */
    public static function uninstallInstaller(): bool
    {
        $pluginPath = JPATH_SITE . '/plugins/system/r2hinstaller/';
        if (!\JFolder::exists($pluginPath)) {
            return self::uninstallInstallerFromDatabase();
        }

        // Remove all files and folders from the plugin directory.
        $dir = new \RecursiveDirectoryIterator($pluginPath, \FilesystemIterator::SKIP_DOTS);
        $it  = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($it as $resource) {
            $resource->isDir()
                ? \JFolder::delete($resource->getRealPath())
                : \JFile::delete($resource->getRealPath());
        }

        // Remove the plugin folder itself.
        \JFolder::delete($pluginPath);

        // Delete the record from the database.
        if (!self::uninstallInstallerFromDatabase()) {
            return false;
        }

        // Clean the cache.
        Factory::getCache()->clean('_system');
        return true;
    }

    /**
     * Uninstall the installer from the database.
     * @access protected
     * @return boolean
     * @static
     */
    protected static function uninstallInstallerFromDatabase(): bool
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query
        ->delete($db->qn('#__extensions'))
        ->where([
            $db->qn('folder') . ' = ' . $db->q('system'),
            $db->qn('element') . ' = ' . $db->q('r2hinstaller'),
            $db->qn('type') . ' = ' . $db->q('plugin'),
        ]);

        $db->setQuery($query);
        return (bool) $db->execute();
    }
}
