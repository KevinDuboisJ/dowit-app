<?php

namespace App\Filament\TiptapExtensions;

use Tiptap\Core\Node;

class CustomImage extends Node
{
  public static $name = 'CustomImage';

  // public static $priority = 100;

  // public function addOptions()
  // {
  //   return [
  //     'HTMLAttributes' => [],
  //   ];
  // }

  // public function parseHTML()
  // {
  //   return [
  //     [
  //       'tag' => 'my-custom-tag[data-id]',
  //     ],
  //     [
  //       'tag' => 'my-custom-tag',
  //       'getAttrs' => function ($DOMNode) {
  //         return ! \Tiptap\Utils\InlineStyle::hasAttribute($DOMNode, [
  //           'background-color' => '#000000',
  //         ]) ? null : false;
  //       },
  //     ],
  //     [
  //       'style' => 'background-color',
  //       'getAttrs' => function ($value) {
  //         return (bool) preg_match('/^(black)$/', $value) ? null : false;
  //       },
  //     ],
  //   ];
  // }

  // public function renderHTML($node)
  // {
  //   return ['my-custom-tag', ['class' => 'foobar'], 0];
  // }
}
