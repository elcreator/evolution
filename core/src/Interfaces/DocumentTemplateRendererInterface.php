<?php

namespace EvolutionCMS\Interfaces;

use EvolutionCMS\Core;

interface DocumentTemplateRendererInterface
{
    /**
     * Render the current document from a file-based template. The selected parser decides whether —
     * and how — the request is rendered from template files on disk (i.e. Blade `.blade.php`).
     * Return the fully rendered HTML when this parser handles the document,
     * or null to fall back to the database template.
     * @param CoreInterface $core
     * @return string|null
     */
    public function renderDocumentTemplate(CoreInterface $core): ?string;

    public function getBaseChunk($name);

    /**
     * @param string $name Template: chunk name || @CODE: template || @FILE: file with template
     * @return string html template with placeholders without data
     */
    public function getChunk($name);

    /**
     * @param string $name Template: chunk name || @CODE: template || @FILE: file with template
     * @param array $data placeholders
     * @param bool $parseDocumentSource render html template via Core::parseDocumentSource()
     * @param bool $disablePHx
     * @return string html template with data without placeholders
     */
    public function parseChunk($name, $data = [], $parseDocumentSource = false, $disablePHx = false);

    /**
     * @param $out
     * @param CoreInterface|null $modx
     * @return mixed|string
     */
    public function parseDocumentSource($out, ?CoreInterface $modx = null);

    /**
     * @return string
     */
    public function getTemplatePath();

    /**
     * @param string $path
     * @param bool $supRoot
     * @return $this
     */
    public function setTemplatePath($path, $supRoot = false);


    public function getTemplateExtension();

    /**
     * @param $ext
     * @return $this
     */
    public function setTemplateExtension($ext);

    /**
     * Additional data for external templates
     *
     * @param array $data
     * @return $this
     */
    public function setTemplateData($data = []);
}
