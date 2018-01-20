<?php
namespace Fasim\View;

class TwigClassExtension extends \Twig_Extension {
    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('className', array($this, 'filterClassName')),
        );
    }

    public function getTests() {
        return array(
            new \Twig_SimpleTest('class', array($this, 'testClass')),
        ); 
    }

    public function filterClassName($class) {
        return get_class($class);
    }

    public function testClass($var) {
        return is_object($var);
    }
}