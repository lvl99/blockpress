<?php
/**
 * A Twig absolute path template loader I copied and pasted from Stackoverflow
 * @see https://stackoverflow.com/questions/7064914/how-to-load-a-template-from-full-path-in-the-template-engine-twig
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Twig_Loader_Abspath implements \Twig_LoaderInterface
{
  protected $paths;
  protected $cache;

  public function __construct()
  {

  }

  public function getSource($name)
  {
    return file_get_contents($this->findTemplate($name));
  }

  public function getCacheKey($name)
  {
    return $this->findTemplate($name);
  }

  public function isFresh($name, $time)
  {
    return filemtime($this->findTemplate($name)) < $time;
  }

  protected function findTemplate($path)
  {
    if(is_file($path)) {
      if (isset($this->cache[$path])) {
        return $this->cache[$path];
      }
      else {
        return $this->cache[$path] = $path;
      }
    }
    else {
      throw new \Twig_Error_Loader(sprintf('Unable to find template "%s".', $path));
    }
  }
}
