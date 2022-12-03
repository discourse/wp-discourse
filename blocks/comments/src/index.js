import { registerBlockType } from '@wordpress/blocks';
import SVG from 'react-inlinesvg';
import edit from './edit';

document.addEventListener("DOMContentLoaded", function() {
  const meta = document.head.querySelector("[name~=wpdc-icon][content]");
  let icon = "";
  let attrs = { example: {}, edit };

  if (meta) {
    const htmlString = atob(meta.content.split(',')[1]);
    attrs.icon = (<SVG src={htmlString} />);
  }

  registerBlockType('wp-discourse/comments', attrs);
});
