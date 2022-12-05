/**
 * WordPress dependencies
 */
import { useBlockProps, useSetting } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import WPDiscourseCommentsPlaceholder from './placeholder';

export default function WPDiscourseCommentsEdit( props ) {
  const blockProps = useBlockProps();
  const defaultLayout = useSetting( 'layout' ) || {};

  // TODO: Workaround for permissions issue described here https://github.com/WordPress/gutenberg/issues/20731
  const urlMeta = document.head.querySelector("[name~=wpdc-url][content]");
  if (urlMeta) {
    props.discourse_url = urlMeta.content;
  }

  return (
    <div { ...blockProps }>
      <WPDiscourseCommentsPlaceholder __experimentalLayout={ defaultLayout } { ...props } />
    </div>
  );
}