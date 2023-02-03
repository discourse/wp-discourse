import { registerBlockType } from '@wordpress/blocks';
import SVG from 'react-inlinesvg';
import edit from './edit';

document.addEventListener("DOMContentLoaded", function() {
  let attrs = {
    example: {},
    edit
  };

  const iconMeta = document.head.querySelector("[name~=wpdc-icon][content]");
  if (iconMeta) {
    const htmlString = atob(iconMeta.content.split(',')[1]);
    attrs.icon = (<SVG src={htmlString} />);
  }

  registerBlockType('wp-discourse/comments', attrs);
});
