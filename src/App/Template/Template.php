<?php


namespace App\Template;


use Closure;

class Template
{
    /** @var array */
    protected $ctx = [];

    /** @var Templater */
    protected $templater;

    /** @var Closure */
    protected $renderer;

    public function __construct(Templater $templater, $path)
    {
        $this->templater = $templater;
        $this->path = $path;

        if (!file_exists($path))
        {
            throw new \LogicException("Passed unexistent template");
        }

        $this->renderer = $this->templater->compileToClosure($path);
    }

    public function get($name, $defaultValue = '')
    {
        return array_key_exists($name, $this->ctx) ?
            $this->ctx[$name] : $defaultValue;
    }

    public function render($depth = 0, array $additionalContext = [])
    {
        $context = array_merge($this->ctx, $additionalContext);
        extract($context);

        /** TODO: this should me moved */
        ob_start();
        require($this->path);
        $content = ob_get_clean();
        $parameters = array_merge($context, $this->templater->extractFromRendered($content));
        $content = trim($content);

        if ($depth > 0)
        {
            $content = $this->templater->get($parameters['wrapperContainer'] ?? 'page_container')
                ->render($depth - 1, $parameters + ['bodyContent' => $content]);
        }

        return $content;
    }

    public function _($template, $data)
    {
        return $this->templater->applyStrTemplate($template, $data);
    }

    public function __invoke(array $additionalContext = [])
    {
        return $this->render(0, $additionalContext);
    }

    /**
     * Escapes the passed string for usage into HTML attributes.
     *
     * @param string $data
     * @return string
     */
    public function forAttr($data)
    {
        return htmlspecialchars($data, ENT_QUOTES);
    }
}