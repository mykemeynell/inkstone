<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\Contracts\NavigationBuilder as NavigationBuilderContract;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\NavigationItem;

final class NavigationBuilder implements NavigationBuilderContract
{
    public function build(array $documents, ?Document $activeDocument = null): array
    {
        $tree = [];

        foreach ($documents as $document) {
            $segments = $document->slug === '' ? [''] : explode('/', $document->slug);
            $this->insert($tree, $segments, $document);
        }

        return $this->sortItems($this->itemsFromNodes($tree, $activeDocument, null));
    }

    /**
     * @param  array<string, array{segment: string, document: Document|null, children: array<string, mixed>}>  $tree
     * @param  list<string>  $segments
     */
    private function insert(array &$tree, array $segments, Document $document): void
    {
        $segment = array_shift($segments) ?? '';
        $key = $segment === '' ? '__root' : $segment;

        if (! isset($tree[$key])) {
            $tree[$key] = [
                'segment' => $segment,
                'document' => null,
                'children' => [],
            ];
        }

        if ($segments === []) {
            $tree[$key]['document'] = $document;

            return;
        }

        $children = $tree[$key]['children'];
        $this->insert($children, $segments, $document);
        $tree[$key]['children'] = $children;
    }

    /**
     * @param  array<string, array{segment: string, document: Document|null, children: array<string, mixed>}>  $nodes
     * @return list<NavigationItem>
     */
    private function itemsFromNodes(array $nodes, ?Document $activeDocument, ?string $parentTitle = null): array
    {
        $items = [];

        foreach ($nodes as $node) {
            $document = $node['document'];
            /** @var array<string, array{segment: string, document: Document|null, children: array<string, mixed>}> $childrenNodes */
            $childrenNodes = $node['children'];

            $title = $document instanceof Document ? $document->title() : $this->titleFromSegment($node['segment']);

            $children = $this->sortItems($this->itemsFromNodes($childrenNodes, $activeDocument, $title));
            $documentIsActive = $document instanceof Document && $activeDocument?->sourcePath === $document->sourcePath;

            if ($children !== [] && $document instanceof Document) {
                array_unshift($children, new NavigationItem(
                    title: 'Overview',
                    url: $document->url,
                    active: $documentIsActive,
                    order: PHP_INT_MIN,
                    sourcePath: $document->sourcePath,
                    headings: $document->headings,
                    slug: '',
                    group: $title,
                ));
            }

            $slug = $document instanceof Document ? $document->slug : $node['segment'];

            $items[] = new NavigationItem(
                title: $title,
                url: $children === [] && $document instanceof Document ? $document->url : '#',
                children: $children,
                active: $documentIsActive || $this->hasActiveChild($children),
                order: $document instanceof Document ? $document->order() : PHP_INT_MAX,
                sourcePath: $document instanceof Document ? $document->sourcePath : null,
                headings: $document instanceof Document ? $document->headings : [],
                slug: $slug,
                group: $parentTitle,
            );
        }

        return $items;
    }

    /**
     * @param  list<NavigationItem>  $items
     * @return list<NavigationItem>
     */
    private function sortItems(array $items): array
    {
        usort($items, static function (NavigationItem $left, NavigationItem $right): int {
            $order = $left->order <=> $right->order;

            return $order !== 0 ? $order : strcasecmp($left->title, $right->title);
        });

        return $items;
    }

    /**
     * @param  list<NavigationItem>  $children
     */
    private function hasActiveChild(array $children): bool
    {
        foreach ($children as $child) {
            if ($child->active || $this->hasActiveChild($child->children)) {
                return true;
            }
        }

        return false;
    }

    private function titleFromSegment(string $segment): string
    {
        return str($segment)->replace(['-', '_'], ' ')->title()->toString();
    }
}
