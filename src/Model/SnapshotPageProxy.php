<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Model;

/**
 * SnapshotPageProxy.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SnapshotPageProxy implements SnapshotPageProxyInterface
{
    /**
     * @var SnapshotManagerInterface
     */
    private $manager;

    /**
     * @var SnapshotInterface
     */
    private $snapshot;

    /**
     * @var PageInterface
     */
    private $page;

    /**
     * @var PageInterface|null
     */
    private $target;

    /**
     * @var PageInterface[]
     */
    private $parents;

    /**
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * @param SnapshotManagerInterface $manager     Snapshot manager
     * @param TransformerInterface     $transformer The transformer object
     * @param SnapshotInterface        $snapshot    Snapshot object
     */
    public function __construct(SnapshotManagerInterface $manager, TransformerInterface $transformer, SnapshotInterface $snapshot)
    {
        $this->manager = $manager;
        $this->snapshot = $snapshot;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->getPage(), $method], $arguments);
    }

    /**
     * Returns a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPage()->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getPage()
    {
        $this->load();

        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function addChildren(PageInterface $children): void
    {
        $this->getPage()->addChildren($children);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers = []): void
    {
        $this->getPage()->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function addHeader($name, $value): void
    {
        $this->getPage()->addHeader($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->getPage()->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        if (!$this->getPage()->getChildren()->count()) {
            $this->getPage()->setChildren(new SnapshotChildrenCollection($this->transformer, $this->getPage()));
        }

        return $this->getPage()->getChildren();
    }

    /**
     * {@inheritdoc}
     */
    public function addBlocks(PageBlockInterface $block): void
    {
        $this->getPage()->addBlocks($block);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlocks()
    {
        if (!count($this->getPage()->getBlocks())) {
            $content = $this->snapshot->getContent();

            foreach ($content['blocks'] as $block) {
                $b = $this->transformer->loadBlock($block, $this);
                $this->addBlocks($b);

                $b->setPage($this);
            }
        }

        return $this->getPage()->getBlocks();
    }

    /**
     * {@inheritdoc}
     */
    public function setTarget(PageInterface $target = null): void
    {
        $this->target = $target;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget()
    {
        if (null === $this->target) {
            $content = $this->snapshot->getContent();

            if (isset($content['target_id'])) {
                $target = $this->manager->findEnableSnapshot([
                    'pageId' => $content['target_id'],
                ]);

                if ($target) {
                    $this->setTarget(new self($this->manager, $this->transformer, $target));
                } else {
                    $this->target = false;
                }
            }
        }

        return $this->target ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent($level = -1)
    {
        $parents = $this->getParents();

        if ($level < 0) {
            $level = count($parents) + $level;
        }

        return $parents[$level] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function setParents(array $parents): void
    {
        $this->parents = $parents;
    }

    /**
     * {@inheritdoc}
     */
    public function getParents()
    {
        if (!$this->parents) {
            $parents = [];

            $snapshot = $this->snapshot;

            while ($snapshot) {
                $content = $snapshot->getContent();

                if (!$content['parent_id']) {
                    break;
                }

                $snapshot = $this->manager->findEnableSnapshot([
                    'pageId' => $content['parent_id'],
                ]);

                if (!$snapshot) {
                    break;
                }

                $parents[] = new self($this->manager, $this->transformer, $snapshot);
            }

            $this->setParents(array_reverse($parents));
        }

        return $this->parents;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(PageInterface $parent = null): void
    {
        $this->getPage()->setParent($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplateCode($templateCode): void
    {
        $this->getPage()->setTemplateCode($templateCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateCode()
    {
        return $this->getPage()->getTemplateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorate($decorate): void
    {
        $this->getPage()->setDecorate($decorate);
    }

    /**
     * {@inheritdoc}
     */
    public function getDecorate()
    {
        return $this->getPage()->getDecorate();
    }

    /**
     * {@inheritdoc}
     */
    public function isHybrid()
    {
        return $this->getPage()->isHybrid();
    }

    /**
     * {@inheritdoc}
     */
    public function setPosition($position): void
    {
        $this->getPage()->setPosition($position);
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return $this->getPage()->getPosition();
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestMethod($method): void
    {
        $this->getPage()->setRequestMethod($method);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod()
    {
        return $this->getPage()->getRequestMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPage()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id): void
    {
        $this->getPage()->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteName()
    {
        return $this->getPage()->getRouteName();
    }

    /**
     * {@inheritdoc}
     */
    public function setRouteName($routeName): void
    {
        $this->getPage()->setRouteName($routeName);
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled): void
    {
        $this->getPage()->setEnabled($enabled);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabled()
    {
        return $this->getPage()->getEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name): void
    {
        $this->getPage()->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getPage()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setSlug($slug): void
    {
        $this->getPage()->setSlug($slug);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug()
    {
        return $this->getPage()->getSlug();
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl($url): void
    {
        $this->getPage()->setUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        return $this->getPage()->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomUrl($customUrl): void
    {
        $this->getPage()->setCustomUrl($customUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomUrl()
    {
        return $this->getPage()->getCustomUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function setMetaKeyword($metaKeyword): void
    {
        $this->getPage()->setMetaKeyword($metaKeyword);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaKeyword()
    {
        return $this->getPage()->getMetaKeyword();
    }

    /**
     * {@inheritdoc}
     */
    public function setMetaDescription($metaDescription): void
    {
        $this->getPage()->setMetaDescription($metaDescription);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaDescription()
    {
        return $this->getPage()->getMetaDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function setJavascript($javascript): void
    {
        $this->getPage()->setJavascript($javascript);
    }

    /**
     * {@inheritdoc}
     */
    public function getJavascript()
    {
        return $this->getPage()->getJavascript();
    }

    /**
     * {@inheritdoc}
     */
    public function setStylesheet($stylesheet): void
    {
        $this->getPage()->setStylesheet($stylesheet);
    }

    /**
     * {@inheritdoc}
     */
    public function getStylesheet()
    {
        return $this->getPage()->getStylesheet();
    }

    /**
     * {@inheritdoc}
     */
    public function getPageAlias()
    {
        return $this->getPage()->getPageAlias();
    }

    /**
     * {@inheritdoc}
     */
    public function setPageAlias($pageAlias)
    {
        return $this->getPage()->setPageAlias($pageAlias);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(\DateTime $createdAt = null): void
    {
        $this->getPage()->setCreatedAt($createdAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getPage()->getCreatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt(\DateTime $updatedAt = null): void
    {
        $this->getPage()->setUpdatedAt($updatedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getPage()->getUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function isDynamic()
    {
        return $this->getPage()->isDynamic();
    }

    /**
     * {@inheritdoc}
     */
    public function isCms()
    {
        return $this->getPage()->isCms();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal()
    {
        return $this->getPage()->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function hasRequestMethod($method)
    {
        return $this->getPage()->hasRequestMethod($method);
    }

    /**
     * {@inheritdoc}
     */
    public function setSite(SiteInterface $site): void
    {
        $this->getPage()->setSite($site);
    }

    /**
     * {@inheritdoc}
     */
    public function getSite()
    {
        return $this->getPage()->getSite();
    }

    /**
     * {@inheritdoc}
     */
    public function setRawHeaders($headers): void
    {
        $this->getPage()->setRawHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getEdited()
    {
        return $this->getPage()->getEdited();
    }

    /**
     * {@inheritdoc}
     */
    public function setEdited($edited): void
    {
        $this->getPage()->setEdited($edited);
    }

    /**
     * {@inheritdoc}
     */
    public function isError()
    {
        return $this->getPage()->isError();
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->getPage()->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title): void
    {
        $this->getPage()->setTitle($title);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type): void
    {
        $this->getPage()->setType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getPage()->getType();
    }

    /**
     * Serialize a snapshot page proxy.
     *
     * @return string
     */
    public function serialize()
    {
        if ($this->manager) {
            return serialize([
                'pageId' => $this->getPage()->getId(),
                'snapshotId' => $this->snapshot->getId(),
            ]);
        }

        return serialize([]);
    }

    /**
     * Unserialize a snapshot page proxy.
     *
     * @param string $serialized
     *
     * @return mixed
     */
    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }

    /**
     * {@inheritdoc}
     */
    private function load(): void
    {
        if (!$this->page && $this->transformer) {
            $this->page = $this->transformer->load($this->snapshot);
        }
    }
}
