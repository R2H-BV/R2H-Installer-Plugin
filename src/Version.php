<?php
/**
 * R2H Installer Plugin
 * @author      Michael Snoeren <michael@r2h.nl>
 * @copyright   R2H Marketing & Internet Solutions © 2018
 * @license     GNU/GPLv3
 */

namespace R2HInstaller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Version as JoomlaVersion;
use R2HInstaller\Exceptions\InvalidVersionException;

defined('_JEXEC') or die;

abstract class Version
{
    /**
     * @var     string $phpVersion The minimal PHP version.
     * @access  protected
     */
    protected static $phpVersion = '7.0.0';

    /**
     * @var     string $joomlaVersion The minimal Joomla! version.
     * @access  protected
     */
    protected static $joomlaVersion = '3.8.0';

    /**
     * Perform all version checks.
     * @access  public
     * @return  boolean
     * @throws  InvalidVersionException Thrown when a version validation failed.
     */
    public static function checkAll(): bool
    {
        if (!self::checkPhpVersion()) {
            throw new InvalidVersionException(
                Text::sprintf(
                    'PLG_SYSTEM_R2HINSTALLER_ERROR_VERSION_PHP',
                    self::$phpVersion,
                    PHP_VERSION
                )
            );
        }

        if (!self::checkJoomlaVersion()) {
            $version = new JoomlaVersion;
            throw new InvalidVersionException(
                Text::sprintf(
                    'PLG_SYSTEM_R2HINSTALLER_ERROR_VERSION_JOOMLA',
                    self::$joomlaVersion,
                    $version->getShortVersion()
                )
            );
        }

        return true;
    }

    /**
     * Check the PHP version.
     * @access  public
     * @return  boolean
     */
    public static function checkPhpVersion(): bool
    {
        return !version_compare(PHP_VERSION, self::$phpVersion, '<');
    }

    /**
     * Check the Joomla! version.
     * @access  public
     * @return  boolean
     */
    public static function checkJoomlaVersion(): bool
    {
        // Build a version string.
        $version = JoomlaVersion::MAJOR_VERSION . '.' . JoomlaVersion::MINOR_VERSION . '.' .
            JoomlaVersion::PATCH_VERSION;

        return !version_compare($version, self::$joomlaVersion, '<');
    }
}
