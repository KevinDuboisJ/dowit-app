import { createElement, createRef, useEffect } from "react";
import tippy, { roundArrow, animateFill } from "tippy.js";
import PropTypes from "prop-types";

const init = (el, props) => {

  tippy(el, {
    plugins: [animateFill],
    content: props.content,
    arrow: roundArrow,
    popperOptions: {
      modifiers: [
        {
          name: "preventOverflow",
          options: {
            rootBoundary: "viewport",
          },
        },
      ],
    },
    animateFill: false,
    animation: "shift-away",
    ...props.options,
  });
};

function Tippy(props) {
  const tippyRef = createRef();

  const defaultProps = {
    content: "",
    tag: "span",
    options: {},
    getRef: () => { },
  };

  props = Object.assign({}, defaultProps, props);

  useEffect(() => {
    props.getRef(tippyRef.current);
    init(tippyRef.current, props);
  }, [props.content]);

  const { content, tag, options, getRef, ...computedProps } = props;
  return createElement(
    props.tag,
    {
      ...computedProps,
      ref: tippyRef,
    },
    props.children
  );
}

Tippy.propTypes = {
  content: PropTypes.string,
  tag: PropTypes.string,
  options: PropTypes.object,
  getRef: PropTypes.func,
};

export default Tippy;
