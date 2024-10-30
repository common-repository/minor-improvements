<?php

namespace MinorImprovements;

if (!defined('ABSPATH')) {
    die();
}

/**
 * Class Entity
 * @package MinorImprovements
 */
class Entity
{
    /** @var int */
    const INT = 1;

    /** @var int */
    const STRING = 2;

    /** @var string */
    const PAGE_QUERY = '?page=mi_options';

    /** @var string */
    const AUTO_UPDATE = 'mi_auto_update';

    /** @var string */
    const UPDATE_NOTIFY = 'mi_update_notify';

    /** @var string */
    const XML_RPC = 'mi_xml_rpc';

    /** @var string */
    const WWW_FIELD = 'mi_www_field';

    /** @var string */
    const AUTHOR_SLUG = 'mi_author_slug';

    /** @var string */
    const RECAPTCHA_SITE_KEY = 'mi_recaptcha_site_key';

    /** @var string */
    const RECAPTCHA_SECRET_KEY = 'mi_recaptcha_secret_key';

    /** @var string */
    const RECAPTCHA_HASH = 'mi_recaptcha_hash';

    /** @var string */
    private $name;

    /** @var int */
    private $type;

    /** @var string|int */
    private $value;

    /**
     * @param string $name
     * @param int $type
     * @param string|int $value
     */
    public function __construct(string $name, int $type, $value = '')
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Entity
     */
    public function setName(string $name): Entity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return Entity
     */
    public function setType(int $type): Entity
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int|string $value
     * @return Entity
     */
    public function setValue($value): Entity
    {
        $this->value = $value;
        return $this;
    }
}
