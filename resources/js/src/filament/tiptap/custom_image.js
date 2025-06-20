import Image from "@tiptap/extension-image";
import {
  mergeAttributes,
  Node,
  nodeInputRule,
} from '@tiptap/core'

/**
 * Matches an image to a ![image](src "title") on input.
 */
export const inputRegex = /(?:^|\s)(!\[(.+|:?)]\((\S+)(?:(?:\s+)["'](\S+)["'])?\))$/

/**
 * This extension allows you to insert images.
 * @see https://www.tiptap.dev/api/nodes/image
 */
export const CustomImage = Image.extend({
  name: 'media',

  addOptions() {
    return {
      allowBase64: true,
      inline:true,
    }
  },

  addAttributes() {
    return {
      src: {
        default: null,
      },
      alt: {
        default: null,
      },
      title: {
        default: null,
      },
      width: {
        default: null,
      },
      height: {
        default: null,
      },
      lazy: {
        default: null,
        parseHTML: element => element.getAttribute('loading') === 'lazy' ? element.getAttribute('data-lazy') : null,
        renderHTML: (attributes) => {
          if (attributes.lazy) {
            return {
              "data-lazy": attributes.lazy,
              "loading": "lazy",
            };
          }
        }
      },
      srcset: {
        default: null,
      },
      sizes: {
        default: null,
      },
      media: {
        default: null,
        parseHTML: element => element.getAttribute('data-media-id'),
        renderHTML: attributes => {
          if (!attributes.media) {
            return {}
          }

          return {
            'data-media-id': attributes.media,
          }
        },
      },

      tag: this.options.allowBase64 ? 'img[src]' : 'img[src]:not([src^="data:"])',
      
    };
  },
})
