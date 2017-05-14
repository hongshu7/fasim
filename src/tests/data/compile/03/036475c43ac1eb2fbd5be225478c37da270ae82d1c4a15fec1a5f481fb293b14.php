<?php

/* error404.html */
class __TwigTemplate_67a757db761bebdbff13ee1b62998da3a4b1ddbf0b2ba1b3489d69828888934a extends Twig_Template
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
<title>HTTP 404</title>
<style type=\"text/css\">
body {
\tbackground-color: #fff;
\tmargin: 40px;
\tfont-family: Lucida Grande, Verdana, Sans-serif;
\tfont-size: 12px;
\tcolor: #000;
}

#content {
\tborder: #999 1px solid;
\tbackground-color: #fff;
\tpadding: 20px 20px 20px 20px;
}

h1 {
\tfont-weight: normal;
\tfont-size: 14px;
\tcolor: #990000;
\tmargin: 0 0 15px 0;
}
</style>
</head>

<body>
\t<div id=\"content\">
\t\t<h1>404 你所访问的页面不存在</h1>
\t\t";
        // line 31
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), ($context["error"] ?? null), "message", array()), "html", null, true);
        echo "
\t</div>
</body>
</html>";
    }

    public function getTemplateName()
    {
        return "error404.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  51 => 31,  19 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("<html>
<head>
<title>HTTP 404</title>
<style type=\"text/css\">
body {
\tbackground-color: #fff;
\tmargin: 40px;
\tfont-family: Lucida Grande, Verdana, Sans-serif;
\tfont-size: 12px;
\tcolor: #000;
}

#content {
\tborder: #999 1px solid;
\tbackground-color: #fff;
\tpadding: 20px 20px 20px 20px;
}

h1 {
\tfont-weight: normal;
\tfont-size: 14px;
\tcolor: #990000;
\tmargin: 0 0 15px 0;
}
</style>
</head>

<body>
\t<div id=\"content\">
\t\t<h1>404 你所访问的页面不存在</h1>
\t\t{{ error.message }}
\t</div>
</body>
</html>", "error404.html", "/Users/kevin/dev/fasim/src/Fasim/View/error404.html");
    }
}
