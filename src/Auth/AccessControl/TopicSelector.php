<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

/**
 * Selects the topic or topics that a permission applies to.
 */
class TopicSelector
{
    private ?string $name;

    private function __construct(?string $name)
    {
        $this->name = $name;
    }

    public static function allTopics(): TopicSelector
    {
        return new TopicSelector(null);
    }

    public static function byName(string $name): TopicSelector
    {
        return new TopicSelector($name);
    }

    public function isAllTopics(): bool
    {
        return $this->name === null;
    }

    /**
     * @return string|null The topic name, or null when all topics are selected.
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
