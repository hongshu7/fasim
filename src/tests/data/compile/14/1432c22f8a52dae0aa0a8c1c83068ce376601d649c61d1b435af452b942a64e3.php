<?php

/* error500.html */
class __TwigTemplate_05955c99a2be1136ab6c654d724940b600d5d3a1cdf8cd6340584a067275050b extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<html>
<head>
<title>Server Error</title>
<style type=\"text/css\">
body {
\tbackground-color: #fff;
\tmargin: 40px;
\tfont-family: Lucida Grande, Verdana, Sans-serif;
\tfont-size: 14px;
\tline-height: 21px;
\tcolor: #000;
}

#content {
\tborder: #999 1px solid;
\tbackground-color: #fff;
\tpadding: 20px 20px;
}

h1 {
\tfont-weight: bold;
\tfont-size: 16px;
\tcolor: #990000;
\tmargin: 0;
\tpadding: 0;
}

.msg {
\tcolor: #ff0000;
\tpadding: 20px 0 10px 0;
}

ul {
\tmargin: 0 0 0 15px;
\tpadding: 0;
\tlist-style: none;
}

li {
\tmargin: 15px 0 0 0; 
\tpadding: 0;
\tlist-style: none;
}

span.file {
\tdisplay: block;
\tcolor: #666;
}

span.func {
\tdisplay: block;
\tmargin-top: 5px; 
\ttext-indent: 24px;
}
</style>
</head>

<body>
\t<div id=\"content\">
\t\t<h1>";
        // line 60
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), ($context["error"] ?? null), "code", array()), "html", null, true);
        echo " Server Error</h1>
\t\t<div class=\"msg\">
\t\t\t";
        // line 62
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), ($context["error"] ?? null), "message", array()), "html", null, true);
        echo "
\t\t</div>
\t\t";
        // line 64
        if (($context["debug"] ?? null)) {
            // line 65
            echo "\t\t<div style=\"margin-top:15px;\">
\t\t\t<hr />
\t\t\t<ul>
\t\t\t\t";
            // line 68
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->getSourceContext(), ($context["error"] ?? null), "trace", array()));
            $context['loop'] = array(
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            );
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
                // line 69
                echo "\t\t\t\t\t<li>
\t\t\t\t\t\t<span class=\"file\">
\t\t\t\t\t\t<strong>#";
                // line 71
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["loop"], "index", array()), "html", null, true);
                echo "</strong>
\t\t\t\t\t\t";
                // line 72
                if (twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "file", array())) {
                    // line 73
                    echo "\t\t\t\t\t\t\t";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "file", array()), "html", null, true);
                    echo ", line ";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "line", array()), "html", null, true);
                    echo "
\t\t\t\t\t\t";
                } else {
                    // line 75
                    echo "\t\t\t\t\t\t\t[internal function]
\t\t\t\t\t\t";
                }
                // line 77
                echo "\t\t\t\t\t\t</span>
\t\t\t\t\t\t<span class=\"func\">
\t\t\t\t\t\t\t";
                // line 79
                if (twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "class", array())) {
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "class", array()), "html", null, true);
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "type", array()), "html", null, true);
                }
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "function", array()), "html", null, true);
                echo "(
\t\t\t\t\t\t\t\t";
                // line 80
                if (twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "args", array())) {
                    // line 81
                    echo "\t\t\t\t\t\t\t\t\t'";
                    echo twig_escape_filter($this->env, twig_join_filter(twig_get_attribute($this->env, $this->getSourceContext(), $context["line"], "args", array()), "', '"), "html", null, true);
                    echo "'
\t\t\t\t\t\t\t\t";
                }
                // line 83
                echo "\t\t\t\t\t\t\t)
\t\t\t\t\t\t
\t\t\t\t\t\t</span>
\t\t
\t\t\t\t\t\t<!-- line.args -->
\t\t\t\t\t</li>
\t\t\t\t";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['line'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 90
            echo "\t\t\t</ul>
\t\t</div>
\t\t";
        }
        // line 93
        echo "\t</div>
</body>
</html>";
    }

    public function getTemplateName()
    {
        return "error500.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  181 => 93,  176 => 90,  156 => 83,  150 => 81,  148 => 80,  140 => 79,  136 => 77,  132 => 75,  124 => 73,  122 => 72,  118 => 71,  114 => 69,  97 => 68,  92 => 65,  90 => 64,  85 => 62,  80 => 60,  19 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("<html>
<head>
<title>Server Error</title>
<style type=\"text/css\">
body {
\tbackground-color: #fff;
\tmargin: 40px;
\tfont-family: Lucida Grande, Verdana, Sans-serif;
\tfont-size: 14px;
\tline-height: 21px;
\tcolor: #000;
}

#content {
\tborder: #999 1px solid;
\tbackground-color: #fff;
\tpadding: 20px 20px;
}

h1 {
\tfont-weight: bold;
\tfont-size: 16px;
\tcolor: #990000;
\tmargin: 0;
\tpadding: 0;
}

.msg {
\tcolor: #ff0000;
\tpadding: 20px 0 10px 0;
}

ul {
\tmargin: 0 0 0 15px;
\tpadding: 0;
\tlist-style: none;
}

li {
\tmargin: 15px 0 0 0; 
\tpadding: 0;
\tlist-style: none;
}

span.file {
\tdisplay: block;
\tcolor: #666;
}

span.func {
\tdisplay: block;
\tmargin-top: 5px; 
\ttext-indent: 24px;
}
</style>
</head>

<body>
\t<div id=\"content\">
\t\t<h1>{{ error.code }} Server Error</h1>
\t\t<div class=\"msg\">
\t\t\t{{ error.message }}
\t\t</div>
\t\t{% if debug %}
\t\t<div style=\"margin-top:15px;\">
\t\t\t<hr />
\t\t\t<ul>
\t\t\t\t{% for line in error.trace %}
\t\t\t\t\t<li>
\t\t\t\t\t\t<span class=\"file\">
\t\t\t\t\t\t<strong>#{{ loop.index }}</strong>
\t\t\t\t\t\t{% if line.file %}
\t\t\t\t\t\t\t{{ line.file }}, line {{ line.line }}
\t\t\t\t\t\t{% else %}
\t\t\t\t\t\t\t[internal function]
\t\t\t\t\t\t{% endif %}
\t\t\t\t\t\t</span>
\t\t\t\t\t\t<span class=\"func\">
\t\t\t\t\t\t\t{% if line.class %}{{ line.class }}{{ line.type }}{% endif %}{{ line.function }}(
\t\t\t\t\t\t\t\t{% if line.args %}
\t\t\t\t\t\t\t\t\t'{{ line.args | join(\"', '\") }}'
\t\t\t\t\t\t\t\t{% endif %}
\t\t\t\t\t\t\t)
\t\t\t\t\t\t
\t\t\t\t\t\t</span>
\t\t
\t\t\t\t\t\t<!-- line.args -->
\t\t\t\t\t</li>
\t\t\t\t{% endfor %}
\t\t\t</ul>
\t\t</div>
\t\t{% endif %}
\t</div>
</body>
</html>", "error500.html", "/Users/kevin/dev/fasim/src/Fasim/View/error500.html");
    }
}
