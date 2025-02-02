import { createElement } from "react";
import * as outline from "@heroicons/react/24/outline";
import * as solid from "@heroicons/react/24/solid";
import PropTypes from "prop-types";

const variants = {
  outline: outline,
  solid: solid,
};

function Heroicon({ icon = "", className = "", variant, ...computedProps }) {
  try {
    // Append the string 'Icon' to the end of the icon name to simplify the creation of icons.
    icon = icon + 'Icon';
    variant = variants[variant] || variants['outline']

    if (variant[icon] !== undefined) {
      return createElement(variant[icon], {
        ...computedProps,
        className: `Heroicon ${className}`,
      });
    } else {
      throw icon;
    }
  } catch (err) {
    throw `Heroicon '${icon}' not found.`;
  }
}

Heroicon.propTypes = {
  icon: PropTypes.string,
  className: PropTypes.string,
};

export default Heroicon;