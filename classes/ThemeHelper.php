<?php namespace Algolia\Core;

class ThemeHelper
{
    private $themes_dir;
    private $module;

    public function __construct($module)
    {
        $this->themes_dir = __DIR__.'/../themes/';
        $this->module = $module;
    }

    public function available_themes()
    {
        $themes = array();

        foreach (scandir($this->themes_dir) as $dir)
        {
            if ($dir[0] != '.')
            {
                $theme = new \stdClass();

                $configs = array();

                if (file_exists($this->themes_dir.$dir.'/config.php'))
                    $configs = include $this->themes_dir.$dir.'/config.php';

                $theme->dir         = $dir;
                $theme->name        = isset($configs['name']) ? $configs['name'] : $dir;

                $theme->screenshot  = isset($configs['screenshot']) ? $configs['screenshot'] : 'screenshot.png';

                if (file_exists($this->themes_dir.$dir.'/'.$theme->screenshot))
                    $theme->screenshot = $this->module->getPath().'/themes/'.$dir.'/'.$theme->screenshot;
                else
                    $theme->screenshot = null;

                $theme->description = isset($configs['description']) ? $configs['description'] : '';

                $themes[] = $theme;
            }
        }

        return $themes;
    }
}