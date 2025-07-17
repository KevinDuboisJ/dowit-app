import DOMPurify from 'dompurify'
import {useEditor, EditorContent} from '@tiptap/react'
import Image from '@tiptap/extension-image'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import {useEffect, useCallback, useRef} from 'react'
import {cn} from '@/utils'

import {Heroicon, Card} from '@/base-components'

export const RichText = ({text, className}) => {
  text = text?.trim()
    ? DOMPurify.sanitize(text, {
        USE_PROFILES: {html: true},
        ALLOWED_TAGS: [
          'a',
          'b',
          'i',
          'strong',
          'em',
          'p',
          'br',
          'ul',
          'li',
          'ol'
        ],
        ADD_ATTR: ['target', 'rel']
      })
    : ''

  return (
    <div
      className={cn(
        className,
        'xl:[&_img]:max-w-[480px]', // Any <img> descendant will get max-width:480px & preserve aspect ratio
        'xl:[&_img]:h-auto',
      )}
      dangerouslySetInnerHTML={{__html: text}}
    ></div>
  )
}

export const RichTextEditor = ({
  value,
  onUpdate,
  className,
  readonly = false
}) => {
  const editor = useEditor({
    content: value,
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,

        autolink: true,

        linkOnPaste: true,

        HTMLAttributes: {
          rel: 'noopener noreferrer',
          target: '_blank'
        }
      }),

      Link.extend({
        inclusive: false
      }),

      Image.configure({
        allowBase64: true
      })
    ],

    editorProps: {
      attributes: {
        class: cn(
          'p-4 h-auto resize-y overflow-hidden border border-0 overflow-y-auto outline-none prose max-w-none',
          className
        )
      }
    },
    onUpdate: ({editor}) => {
      if (!readonly) {
        const content = editor.getText() ? editor.getHTML() : ''
        onUpdate(content)
      }
    }
  })

  if (!editor) {
    return null
  }

  // Ensure the editor is updated with the initial value when it first mounts
  useEffect(() => {
    if (editor && editor.getHTML() !== value) {
      editor.commands.setContent(value)
    }
  }, [value, editor])

  if (readonly) {
    editor.setEditable(false)
  }

  return (
    <Card className="rounded shadow-xs bg-transparent">
      {!readonly && <RichTextToolbar editor={editor} />}
      <EditorContent editor={editor} spellCheck={false} />
    </Card>
  )
}

const RichTextToolbar = ({editor}) => {
  const setLink = useCallback(() => {
    const previousUrl = editor.getAttributes('link').href
    const url = window.prompt('URL', previousUrl)

    // cancelled
    if (url === null) {
      return
    }

    // empty
    if (url === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run()

      return
    }

    // update link
    try {
      editor.chain().focus().extendMarkRange('link').setLink({href: url}).run()
    } catch (e) {
      alert(e.message)
    }
  }, [editor])

  return (
    <div className="flex p-1 bg-neutral-100 rounded border-b border-gray-100 shadow-none">
      <button
        type="button"
        onClick={() => editor.chain().focus().toggleBold().run()}
        className={`p-1 ${
          editor.isActive('bold') ? 'bg-gray-200 rounded' : ''
        }`}
      >
        <Heroicon icon="Bold" className="h-4 w-4" />
      </button>

      <button
        type="button"
        onClick={() => editor.chain().focus().toggleItalic().run()}
        className={`p-1 ${
          editor.isActive('italic') ? 'bg-gray-200 rounded' : ''
        }`}
      >
        <Heroicon icon="Italic" className="h-4 w-4" />
      </button>

      <button
        type="button"
        onClick={setLink}
        className={`p-1  ${
          editor.isActive('link') ? 'bg-gray-200 rounded' : ''
        }`}
      >
        <Heroicon icon="Link" className="h-4 w-4" />
      </button>

      <CameraButton
        callback={base64 =>
          editor.chain().focus().setImage({src: base64}).run()
        }
      />

      {/* <button
    type="button"
    onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
    className={`p-1 ${editor.isActive('heading', { level: 1 }) ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
    className={`p-1 ${editor.isActive('heading', { level: 2 }) ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().toggleBulletList().run()}
    className={`p-1 ${editor.isActive('bulletList') ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().toggleOrderedList().run()}
    className={`p-1 ${editor.isActive('orderedList') ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().toggleBlockquote().run()}
    className={`p-1 ${editor.isActive('blockquote') ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().toggleCode().run()}
    className={`p-1 ${editor.isActive('code') ? 'bg-gray-200 rounded' : ''}`}
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().setHorizontalRule().run()}
    className="p-1"
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().undo().run()}
    disabled={!editor.can().chain().focus().undo().run()}
    className="p-1 disabled:text-gray-400"
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button>
  <button
    type="button"
    onClick={() => editor.chain().focus().redo().run()}
    disabled={!editor.can().chain().focus().redo().run()}
    className="p-1 disabled:text-gray-400"
  >
    <Heroicon icon="Bell" className="h-5 w-5" />
  </button> */}
    </div>
  )
}

const CameraButton = ({callback}) => {
  const cameraInputRef = useRef(null)

  const handleOpenCamera = () => {
    cameraInputRef.current?.click()
  }

  const handleCameraCapture = async e => {
    const file = e.target.files[0]
    if (!file) return

    const resizedBase64Image = await resizeAndCompressImage(file) // max width 1200px, 80% quality
    callback(resizedBase64Image)

    e.target.value = '' // allow retaking photo without refresh
  }

  const resizeAndCompressImage = (file, maxDimension = 800, quality = 0.8) => {
    return new Promise((resolve, reject) => {
      if (typeof window === 'undefined') {
        reject(new Error('This function must run in a browser environment'))
        return
      }

      const img = new window.Image()
      const reader = new FileReader()

      reader.onload = e => {
        img.src = e.target.result
      }

      img.onerror = () => reject(new Error('Failed to load image'))

      img.onload = () => {
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')

        let {width, height} = img

        // Determine which side is the “limiting” one
        if (width >= height) {
          // Landscape or square: scale by width
          if (width > maxDimension) {
            const scaleFactor = maxDimension / width
            width = maxDimension
            height = Math.round(height * scaleFactor)
          }
        } else {
          // Portrait: scale by height
          if (height > maxDimension) {
            const scaleFactor = maxDimension / height
            height = maxDimension
            width = Math.round(width * scaleFactor)
          }
        }

        canvas.width = width
        canvas.height = height

        ctx.drawImage(img, 0, 0, width, height)

        // Export as JPEG with the given quality
        const compressedBase64 = canvas.toDataURL('image/jpeg', quality)
        resolve(compressedBase64)
      }

      reader.readAsDataURL(file)
    })
  }

  return (
    <>
      {/* Camera button */}
      <button type="button" onClick={handleOpenCamera} className="p-1">
        <Heroicon icon="Camera" className="h-4 w-4" />
      </button>

      {/* Hidden camera input */}
      <input
        type="file"
        accept="image/*"
        capture="environment"
        style={{display: 'none'}}
        ref={cameraInputRef}
        onChange={handleCameraCapture}
      />
    </>
  )
}
