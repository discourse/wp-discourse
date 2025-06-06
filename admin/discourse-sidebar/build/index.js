/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__);

/*jshint esversion: 6*/
/*global pluginOptions*/
/**
 * Internal block libraries.
 */

const {
  __
} = wp.i18n;
const {
  PluginSidebar,
  PluginSidebarMoreMenuItem
} = wp.editor;
const {
  PanelBody
} = wp.components;
const {
  Component,
  Fragment
} = wp.element;
const {
  withSelect,
  withDispatch
} = wp.data;
const {
  compose
} = wp.compose;
const {
  registerPlugin
} = wp.plugins;
const el = wp.element.createElement;

// See: https://wp.zacgordon.com/2017/12/07/how-to-add-custom-icons-to-gutenberg-editor-blocks-in-wordpress/
const iconEl = el('img', {
  width: 20,
  height: 20,
  src: pluginOptions.logo,
  class: 'wpdc-logo'
});
const buttonClass = 'components-button is-button is-default is-primary is-large wpdc-button';
const activeButtonClass = 'components-button is-button is-default is-primary is-large wpdc-button active';
const downArrow = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("svg", {
  className: 'components-panel__arrow',
  width: "24px",
  height: "24px",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  role: "img",
  "aria-hidden": "true",
  focusable: "false",
  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("g", {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("path", {
      fill: 'none',
      d: 'M0,0h24v24H0V0z'
    })
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("g", {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("path", {
      d: 'M7.41,8.59L12,13.17l4.59-4.58L18,10l-6,6l-6-6L7.41,8.59z'
    })
  })]
});
const upArrow = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("svg", {
  className: 'components-panel__arrow',
  width: "24px",
  height: "24px",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  role: "img",
  "aria-hidden": "true",
  focusable: "false",
  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("g", {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("path", {
      fill: 'none',
      d: 'M0,0h24v24H0V0z'
    })
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("g", {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("path", {
      d: 'M12,8l-6,6l1.41,1.41L12,10.83l4.59,4.58L18,14L12,8z'
    })
  })]
});
const tagsFilterRegExp = /[\/\?#\[\]@!\$&'\(\)\*\+,;=\.%\\`^\s|\{\}"<>]+/;
class Notification extends Component {
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(ForcePublishMessage, {
        forcePublish: this.props.forcePublish,
        published: this.props.published
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(StatusMessage, {
        statusMessage: this.props.statusMessage
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(ErrorMessage, {
        publishingError: this.props.publishingError
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(DiscoursePermalink, {
        discoursePermalink: this.props.discoursePermalink
      })]
    });
  }
}
class ForcePublishMessage extends Component {
  render() {
    if (this.props.forcePublish && !this.props.published) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
        className: 'wpdc-force-publish-message',
        children: __('The Force Publish option is enabled for your site. All posts published on WordPress will be automatically published to Discourse.', 'wp-discourse')
      });
    }
    return '';
  }
}
class StatusMessage extends Component {
  render() {
    const statusMessage = this.props.statusMessage;
    if (statusMessage) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
        className: 'wpdc-publishing-response success',
        children: statusMessage
      });
    }
    return '';
  }
}
class ErrorMessage extends Component {
  render() {
    const publishingError = this.props.publishingError;
    if (publishingError) {
      let message;
      switch (publishingError) {
        case 'deleted_topic':
          message = __('Your post could not be published to Discourse. The associated Discourse topic may have been deleted. ' + 'Unlink the post so that it can be published again.', 'wp-discourse');
          break;
        case 'Not Found':
          message = __('Your post could not be updated on Discourse. The associated Discourse topic may have been deleted. ' + 'Unlink the post so that it can be published again.', 'wp-discourse');
          break;
        case 'queued_topic':
          message = __('Your post has been added to the Discourse approval queue. When it has been approved, you will need to link it to Discourse by' + "selecting the 'Link to Existing Topic' option.", 'wp-discourse');
          break;
        case 'Unprocessable Entity':
          message = __('Your post could not be published to Discourse. There may be an existing Discourse topic that is using its permalink. Try linking the post with that topic.', 'wp-discourse');
          break;
        case 'Forbidden':
          message = __('Your post could not be published to Discourse. Check that your Discourse Username is set correctly on your WordPress profile page.', 'wp-discourse');
          break;
        default:
          message = publishingError;
      }
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
        className: 'wpdc-publishing-response error',
        children: message
      });
    }
    return '';
  }
}
class DiscoursePermalink extends Component {
  render() {
    if (this.props.discoursePermalink) {
      const permalink = encodeURI(this.props.discoursePermalink);
      const link = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
        href: permalink,
        className: 'wpdc-permalink-link',
        target: '_blank',
        rel: 'noreferrer noopener',
        children: permalink
      });
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: 'wpdc-permalink',
        children: [__('Your post is linked with', 'wp-discourse'), ' ', link]
      });
    }
    return '';
  }
}
class PublishingOptions extends Component {
  constructor(props) {
    super(props);
    this.handleChange = this.handleChange.bind(this);
  }
  handleChange(e) {
    this.props.handlePublishMethodChange(e.target.value);
  }
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-publishing-options',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-sidebar-title',
        children: __('Publishing Options', 'wp-discourse')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("label", {
        htmlFor: "wpdc_publish_option_new_topic",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          id: "wpdc_publish_option_new_topic",
          type: "radio",
          name: "wpdc_publish_options",
          value: "publish_post",
          checked: 'publish_post' === this.props.publishingMethod,
          onChange: this.handleChange
        }), __('New Topic', 'wp-discourse')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("label", {
        htmlFor: "wpdc_publish_option_existing_topic",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          id: "wpdc_publish_option_existing_topic",
          type: "radio",
          name: "wpdc_publish_options",
          value: "link_post",
          checked: 'link_post' === this.props.publishingMethod,
          onChange: this.handleChange
        }), __('Link to Existing Topic', 'wp-discourse')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("hr", {
        className: 'wpdc-sidebar-hr'
      })]
    });
  }
}
class PublishToDiscourse extends Component {
  constructor(props) {
    super(props);
    this.handleToBePublishedChange = this.handleToBePublishedChange.bind(this);
    this.handlePublishChange = this.handlePublishChange.bind(this);
  }
  handleToBePublishedChange(e) {
    this.props.handleToBePublishedChange(e.target.checked);
  }
  handlePublishChange(e) {
    this.props.handlePublishChange(e);
  }
  render() {
    const publishToDiscourse = this.props.publishToDiscourse,
      publishedOnWordPress = 'publish' === this.props.postStatus;
    if (!publishedOnWordPress) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: 'wpdc-component-panel-body',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
          className: 'wpdc-sidebar-title',
          children: __('Publish to Discourse', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
          className: 'wpdc-publish-topic',
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
            type: "checkBox",
            className: 'wpdc-publish-topic-checkbox',
            checked: publishToDiscourse,
            onChange: this.handleToBePublishedChange
          }), __('Publish', 'wp-discourse'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
            className: 'wpdc-info',
            children: __('Automatically publish the post to Discourse when it is published on WordPress.', 'wp-discourse')
          })]
        })]
      });
    }
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-component-panel-body',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-sidebar-title',
        children: __('Publish to Discourse', 'wp-discourse')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
        className: this.props.busy ? activeButtonClass : buttonClass,
        onClick: this.handlePublishChange,
        children: __('Publish to Discourse', 'wp-discourse')
      })]
    });
  }
}
class CategorySelect extends Component {
  constructor(props) {
    super(props);
    this.handleChange = this.handleChange.bind(this);
  }
  handleChange(e) {
    this.props.handleCategoryChange(e.target.value);
  }
  render() {
    if (this.props.discourseCategories) {
      const cats = Object.values(this.props.discourseCategories);
      const options = cats.map((cat, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("option", {
        value: cat.id,
        selected: parseInt(this.props.category_id, 10) === parseInt(cat.id, 10),
        children: cat.name
      }, index));
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: 'wpdc-category-select wpdc-component-panel-body',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
          className: 'wpdc-sidebar-title',
          children: __('Category', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("select", {
          onChange: this.handleChange,
          className: 'widefat',
          children: options
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("hr", {
          className: 'wpdc-sidebar-hr'
        })]
      });
    } else if (this.props.categoryError) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
        className: 'wpdc-api-error error',
        children: __('There was an error returning the category list from Discourse.', 'discourse-integration')
      });
    }
    return null;
  }
}
class LinkToDiscourseTopic extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isBusy: false,
      topicUrl: null
    };
    this.handleChange = this.handleChange.bind(this);
    this.handleClick = this.handleClick.bind(this);
  }
  handleChange(e) {
    this.setState({
      topicUrl: e.target.value
    });
  }
  handleClick(/*e*/
  ) {
    this.props.handleLinkTopicClick(this.state.topicUrl);
  }
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-link-post wpdc-component-panel-body',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-sidebar-title',
        children: __('Topic URL', 'wp-discourse')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
        type: "url",
        className: 'widefat wpdc-topic-url-input',
        onChange: this.handleChange,
        value: this.state.topicUrl
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
        className: this.props.busy ? activeButtonClass : buttonClass,
        onClick: this.handleClick,
        children: __('Link With Discourse', 'wp-discourse')
      })]
    });
  }
}
class UnlinkFromDiscourse extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showPanel: false
    };
    this.handleClick = this.handleClick.bind(this);
    this.togglePanel = this.togglePanel.bind(this);
  }
  handleClick(e) {
    this.props.handleUnlinkFromDiscourseChange(e);
  }
  togglePanel() {
    this.setState({
      showPanel: !this.state.showPanel
    });
  }
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-component-panel-body',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-panel-section-title',
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("button", {
          type: "button",
          "aria-expanded": "false",
          className: 'components-button components-panel__body-toggle',
          onClick: this.togglePanel,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
            "aria-hidden": "true",
            children: this.state.showPanel ? upArrow : downArrow
          }), __('Unlink From Discourse', 'wp-discourse')]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: !this.state.showPanel ? 'hidden' : '',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
          className: 'wpdc-info',
          children: __('Unlinking the post from Discourse will remove all Discourse metadata from the post.', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
          className: this.props.busy ? activeButtonClass : buttonClass,
          onClick: this.handleClick,
          children: __('Unlink Post', 'wp-discourse')
        })]
      })]
    });
  }
}
class UpdateDiscourseTopic extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showPanel: false
    };
    this.handleClick = this.handleClick.bind(this);
    this.togglePanel = this.togglePanel.bind(this);
  }
  togglePanel() {
    this.setState({
      showPanel: !this.state.showPanel
    });
  }
  handleClick(e) {
    this.props.handleUpdateChange(e);
  }
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-component-panel-body',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-panel-section-title',
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("button", {
          type: "button",
          "aria-expanded": "false",
          className: 'components-button components-panel__body-toggle',
          onClick: this.togglePanel,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
            "aria-hidden": "true",
            children: this.state.showPanel ? upArrow : downArrow
          }), __('Update Discourse Topic', 'wp-discourse')]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: !this.state.showPanel ? 'hidden' : '',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
          className: 'wpdc-info',
          children: __('Update the Discourse topic to the lastest saved version of the post.', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
          className: this.props.busy ? activeButtonClass : buttonClass,
          onClick: this.handleClick,
          children: __('Update Topic', 'wp-discourse')
        })]
      })]
    });
  }
}
class TagTopic extends Component {
  constructor(props) {
    super(props);
    this.state = {
      chosenTags: this.props.tags,
      inputContent: '',
      inputLength: 1,
      maxTagsExceeded: false
    };
    this.handleKeyPress = this.handleKeyPress.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.handleClick = this.handleClick.bind(this);
    this.focusInput = this.focusInput.bind(this);
    this.keyDown = this.keyDown.bind(this);
  }
  focusInput(/*e*/
  ) {
    this.tagInput.focus();
  }
  keyDown(e) {
    if (e.keyCode === 13) {
      this.tagInput.focus();
    }
  }
  handleChange(e) {
    const val = e.target.value;
    this.setState({
      inputContent: ',' === val ? '' : val,
      inputLength: val.length === 0 ? 1 : val.length
    });
  }
  handleKeyPress(e) {
    const keyVal = e.key;
    let val = e.target.value;
    if ('Enter' === keyVal || ',' === keyVal) {
      let currentChoices = this.state.chosenTags;
      if (currentChoices.length >= this.props.maxTags) {
        this.setState({
          maxTagsExceeded: true,
          inputContent: ''
        });
        return null;
      }
      val = this.cleanTag(val);
      if (val.length) {
        currentChoices.push(val);
        currentChoices = TagTopic.sanitizeArray(currentChoices);
        this.setState({
          chosenTags: currentChoices,
          inputContent: ''
        }, () => {
          this.props.handleTagChange(currentChoices);
        });
      } else {
        this.setState({
          inputContent: ''
        });
      }
    }
  }

  // see discourse/lib/discourse_tagging.rb#clean_tag
  cleanTag(val) {
    val = val.trim(); // remove surrounding whitespace
    val = val.replace(/ /g, '-'); // replace whitespace with hyphen
    val = val.replace(/(-)\1+/g, '-'); // remove duplicate hyphens
    val = val.replace(new RegExp(tagsFilterRegExp, 'g'), ''); // remove special characters
    return val;
  }
  handleClick(key) {
    const tags = this.state.chosenTags;
    const index = tags.indexOf(key);
    if (index > -1) {
      tags.splice(index, 1);
      this.setState({
        chosenTags: tags,
        maxTagsExceeded: false
      }, () => {
        this.props.handleTagChange(tags);
      });
    }
  }
  static sanitizeArray(arr) {
    arr = arr.sort().reduce((accumulator, current) => {
      const length = accumulator.length;
      if ((0 === length || accumulator[length - 1] !== current) && current.trim() !== '') {
        accumulator.push(current);
      }
      return accumulator;
    }, []);
    return arr;
  }
  render() {
    if (this.props.allowTags) {
      const maxTagsNotice = this.state.maxTagsExceeded ? __('You have exceeded the maximum number of allowed tags for your site. Remove a tag to add more.', 'wp-discourse') : '';
      let tagDisplay = TagTopic.sanitizeArray(this.state.chosenTags);
      tagDisplay = tagDisplay.map(tag => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
        className: 'components-form-token-field__token',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
          className: 'components-form-token-field__token-text',
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
            className: 'screen-reader-text',
            children: tag
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
            "aria-hidden": "true",
            children: tag
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
          type: "button",
          "aria-label": "Remove Tag",
          className: 'components-button components-icon-button components-form-token-field__remove-token',
          onClick: this.handleClick.bind(this, tag),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", {
            "aria-hidden": "true",
            role: "img",
            focusable: "false",
            className: 'dashicon dashicons-dismiss',
            xmlns: "http://www.w3.org/2000/svg",
            width: "20",
            height: "20",
            viewBox: "0 0 20 20",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("path", {
              d: 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm5 11l-3-3 3-3-2-2-3 3-3-3-2 2 3 3-3 3 2 2 3-3 3 3z'
            })
          })
        }, tag)]
      }, tag));
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: 'wpdc-component-panel-body',
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
          className: 'wpdc-sidebar-title',
          children: __('Tags', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
          className: 'components-form-token-field__input-container',
          onClick: this.focusInput,
          onKeyDown: this.keyDown,
          role: "combobox",
          tabIndex: 0,
          children: [tagDisplay, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
            type: 'text',
            size: this.state.inputLength,
            className: 'components-form-token-field__input',
            onChange: this.handleChange,
            onKeyPress: this.handleKeyPress,
            value: this.state.inputContent,
            ref: input => {
              this.tagInput = input;
            }
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
          className: this.state.maxTagsExceeded ? 'wpdc-info' : '',
          children: maxTagsNotice
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("hr", {
          className: 'wpdc-sidebar-hr'
        })]
      });
    }
    return null;
  }
}
class PinTopic extends Component {
  constructor(props) {
    super(props);
    this.state = {
      pinTopic: this.props.pinTopic,
      pinUntil: this.props.pinUntil
    };
    this.handleUpdateDate = this.handleUpdateDate.bind(this);
    this.handleToBePinnedChange = this.handleToBePinnedChange.bind(this);
  }
  handleUpdateDate(e) {
    this.setState({
      pinUntil: e.target.value
    });
    this.props.handlePinChange(this.state.pinTopic, e.target.value);
  }
  handleToBePinnedChange(e) {
    this.setState({
      pinTopic: e.target.checked
    });
    this.props.handlePinChange(e.target.checked, this.state.pinUntil);
  }
  render() {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: 'wpdc-component-panel-body',
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
        className: 'wpdc-sidebar-title',
        children: __('Pin Topic', 'wp-discourse')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("label", {
        htmlFor: "wpdc-pin-topic",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          id: "wpdc-pin-topic",
          type: 'checkbox',
          onChange: this.handleToBePinnedChange,
          checked: this.state.pinTopic
        }), __('Pin Discourse Topic', 'wp-discourse')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("label", {
        className: 'wpdc-pin-until-input',
        htmlFor: "wpdc-pin-topic-until",
        children: [__('Pin Until', 'wp-discourse'), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          id: "wpdc-pin-topic-until",
          type: 'date',
          className: 'widefat',
          onChange: this.handleUpdateDate,
          value: this.state.pinUntil
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("hr", {
        className: 'wpdc-sidebar-hr'
      })]
    });
  }
}
class DiscourseSidebar extends Component {
  constructor(props) {
    super(props);
    this.initializePostMeta();
    this.state = this.initializePostState(this.props.post);
    this.getDiscourseCategories();
    this.handleToBePublishedChange = this.handleToBePublishedChange.bind(this);
    this.handlePublishChange = this.handlePublishChange.bind(this);
    this.handleCategoryChange = this.handleCategoryChange.bind(this);
    this.handleTagChange = this.handleTagChange.bind(this);
    this.handleUnlinkFromDiscourseChange = this.handleUnlinkFromDiscourseChange.bind(this);
    this.handlePublishMethodChange = this.handlePublishMethodChange.bind(this);
    this.handleUpdateChange = this.handleUpdateChange.bind(this);
    this.handleLinkTopicClick = this.handleLinkTopicClick.bind(this);
    this.handlePinChange = this.handlePinChange.bind(this);
  }
  componentDidUpdate(prevProps) {
    if (this.isAllowedPostType()) {
      if (this.publishedPostHasChanged(prevProps.post, this.props.post)) {
        this.updatePostState(this.props.post);
      }
    }
  }
  publishedPostHasChanged(prev, post) {
    if (!prev || !post || !prev.meta || !post.meta) {
      return false;
    }

    // We don't refresh state if post is not yet published
    if ([post.status, prev.status].every(s => s !== 'publish')) {
      return false;
    }

    // We always refresh state on a status change
    if (post.status !== prev.status) {
      return true;
    }

    // We refresh state on publishing error or linked post change
    return ['discourse_post_id', 'wpdc_publishing_response', 'wpdc_publishing_error'].some(attr => post.meta[attr] !== prev.meta[attr]);
  }
  initializePostMeta() {
    if (!this.isAllowedPostType()) {
      return;
    }

    // If the post is already published and auto publish option is enabled, set auto publish overridden.
    if (this.props.post && this.props.post.status === 'publish') {
      this.props.setAutoPublishOverridden('1');
    }
  }
  initializePostState(post) {
    if (!this.isAllowedPostType()) {
      return {};
    }
    let state = {
      published: false,
      postStatus: '',
      publishingMethod: 'publish_post',
      forcePublish: pluginOptions.forcePublish,
      publishToDiscourse: pluginOptions.autoPublish,
      publishPostCategory: pluginOptions.defaultCategory,
      allowTags: pluginOptions.allowTags,
      maxTags: pluginOptions.maxTags,
      topicTags: [],
      pinTopic: false,
      pinUntil: null,
      discoursePostId: null,
      discoursePermalink: null,
      publishingError: null,
      busyUnlinking: false,
      busyUpdating: false,
      busyLinking: false,
      busyPublishing: false,
      statusMessage: null,
      discourseCategories: null,
      categoryError: false
    };
    if (post && post.meta) {
      state = Object.assign(state, this.buildPostState(post));
    }
    return state;
  }
  updatePostState(post) {
    this.setState(this.buildPostState(post));
  }
  buildPostState(post) {
    if (!post || !post.meta) {
      return {};
    }
    const meta = post.meta;
    const topicTags = typeof meta.wpdc_topic_tags === 'string' ? meta.wpdc_topic_tags.split(',') : [];
    const postState = {
      publishToDiscourse: this.determinePublishToDiscourse(meta),
      published: meta.discourse_post_id > 0,
      postStatus: post.status,
      topicTags,
      pinTopic: meta.wpdc_pin_topic > 0
    };
    if (meta.publish_post_category > 0) {
      postState.publishPostCategory = meta.publish_post_category;
    }
    if (meta.wpdc_pin_until) {
      postState.pinUntil = meta.wpdc_pin_until;
    }
    if (meta.discourse_post_id) {
      postState.discoursePostId = meta.discourse_post_id;
    }
    if (meta.discourse_permalink) {
      postState.discoursePermalink = meta.discourse_permalink;
    }
    if (meta.wpdc_publishing_error) {
      postState.publishingError = meta.wpdc_publishing_error;
    }
    return postState;
  }
  determinePublishToDiscourse(meta) {
    const autoPublish = pluginOptions.autoPublish,
      autoPublishOverridden = 1 === parseInt(meta.wpdc_auto_publish_overridden, 10);
    let publishToDiscourse;
    if (['deleted_topic', 'queued_topic'].includes(meta.wpdc_publishing_error)) {
      publishToDiscourse = false;
    } else if (autoPublish && !autoPublishOverridden) {
      publishToDiscourse = true;
    } else {
      publishToDiscourse = 1 === parseInt(meta.wpdc_publish_to_discourse, 10);
    }
    return publishToDiscourse;
  }
  getDiscourseCategories() {
    if (!pluginOptions.pluginUnconfigured) {
      wp.apiRequest({
        path: '/wp-discourse/v1/get-discourse-categories',
        method: 'GET',
        data: {
          get_categories_nonce: pluginOptions.get_categories_nonce,
          id: this.props.postId
        }
      }).then(data => {
        this.setState({
          discourseCategories: data
        });
      }, (/*err*/
      ) => {
        this.setState({
          categoryError: true
        });
      });
    }
  }
  isAllowedPostType() {
    return pluginOptions.allowedPostTypes.indexOf(this.props.post.type) >= 0;
  }
  handlePublishMethodChange(publishingMethod) {
    this.setState({
      publishingMethod
    });
  }
  handleToBePublishedChange(publishToDiscourse) {
    this.setState({
      publishToDiscourse,
      statusMessage: ''
    }, () => {
      wp.apiRequest({
        path: '/wp-discourse/v1/set-publish-meta',
        method: 'POST',
        data: {
          set_publish_meta_nonce: pluginOptions.set_publish_meta_nonce,
          id: this.props.postId,
          publish_to_discourse: this.state.publishToDiscourse ? 1 : 0
        }
      }).then((/*data*/
      ) => {
        return null;
      }, (/*err*/
      ) => {
        return null;
      });
    });
  }
  handleCategoryChange(categoryId) {
    this.setState({
      publishPostCategory: categoryId
    }, () => {
      wp.apiRequest({
        path: '/wp-discourse/v1/set-category-meta',
        method: 'POST',
        data: {
          set_category_meta_nonce: pluginOptions.set_category_meta_nonce,
          id: this.props.postId,
          publish_post_category: categoryId
        }
      }).then((/*data*/
      ) => {
        return null;
      }, (/*err*/
      ) => {
        return null;
      });
    });
  }
  handlePinChange(pinTopic, pinUntil) {
    this.setState({
      pinTopic,
      pinUntil
    }, () => {
      wp.apiRequest({
        path: '/wp-discourse/v1/set-pin-meta',
        method: 'Post',
        data: {
          set_pin_meta_nonce: pluginOptions.set_pin_meta_nonce,
          id: this.props.postId,
          wpdc_pin_topic: pinTopic ? 1 : 0,
          wpdc_pin_until: pinUntil
        }
      }).then((/*data*/
      ) => {
        return null;
      }, (/*err*/
      ) => {
        return null;
      });
    });
  }
  handleTagChange(tags) {
    this.setState({
      topicTags: tags
    }, () => {
      const tagString = tags.join(',');
      wp.apiRequest({
        path: '/wp-discourse/v1/set-tag-meta',
        method: 'POST',
        data: {
          set_tag_meta_nonce: pluginOptions.set_tag_meta_nonce,
          id: this.props.postId,
          wpdc_topic_tags: tagString
        }
      }).then((/*data*/
      ) => {
        return null;
      }, (/*err*/
      ) => {
        return null;
      });
    });
  }
  handleLinkTopicClick(topicUrl) {
    this.setState({
      busyLinking: true,
      statusMessage: ''
    });
    wp.apiRequest({
      path: '/wp-discourse/v1/link-topic',
      method: 'POST',
      data: {
        link_topic_nonce: pluginOptions.link_topic_nonce,
        id: this.props.postId,
        topic_url: topicUrl
      }
    }).then(data => {
      this.setState({
        busyLinking: false
      });
      if (data.discourse_permalink) {
        this.setState({
          published: true,
          discoursePermalink: data.discourse_permalink,
          publishingError: null
        });
      } else {
        this.setState({
          publishingError: __('There has been an error linking your post with Discourse.', 'wp-discourse')
        });
      }
      return null;
    }, err => {
      const message = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : __('There has been an error linking your post with Discourse.', 'wp-discourse');
      this.setState({
        busyLinking: false,
        published: false,
        publishingError: message
      });
      return null;
    });
  }
  handleUnlinkFromDiscourseChange(/*e*/
  ) {
    this.setState({
      busyUnlinking: true,
      statusMessage: ''
    });
    wp.apiRequest({
      path: '/wp-discourse/v1/unlink-post',
      method: 'POST',
      data: {
        unlink_post_nonce: pluginOptions.unlink_post_nonce,
        id: this.props.postId
      }
    }).then((/*data*/
    ) => {
      this.setState({
        busyUnlinking: false,
        published: false,
        publishingMethod: 'link_post',
        discoursePermalink: null,
        statusMessage: __('Your post has been unlinked from Discourse.', 'wp-discourse')
      });
      return null;
    }, (/*err*/
    ) => {
      return null;
    });
  }
  handlePublishChange(/*e*/
  ) {
    this.setState({
      busyPublishing: true,
      statusMessage: ''
    });
    wp.apiRequest({
      path: '/wp-discourse/v1/publish-topic',
      method: 'POST',
      data: {
        publish_topic_nonce: pluginOptions.publish_topic_nonce,
        id: this.props.postId
      }
    }).then(data => {
      const success = 'success' === data.publish_response;
      this.setState({
        busyPublishing: false,
        published: success,
        publishingError: success ? null : data.publish_response,
        publishingMethod: data.publish_response =  true ? 'link_post' : 0,
        discoursePermalink: data.discourse_permalink
      });
      return null;
    }, err => {
      const message = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : __('There has been an error linking your post with Discourse.', 'wp-discourse');
      this.setState({
        busyPublishing: false,
        published: false,
        publishingError: message
      });
      return null;
    });
  }
  handleUpdateChange(/*e*/
  ) {
    this.setState({
      busyUpdating: true,
      statusMessage: ''
    });
    wp.apiRequest({
      path: '/wp-discourse/v1/update-topic',
      method: 'POST',
      data: {
        update_topic_nonce: pluginOptions.update_topic_nonce,
        id: this.props.postId
      }
    }).then(data => {
      const response = data.update_response,
        success = 'success' === response;
      let message;
      if (success) {
        message = __('The Discourse topic has been updated!', 'wp-discourse');
      }
      this.setState({
        busyUpdating: false,
        statusMessage: message,
        publishingError: success ? null : data.update_response
      });
      return null;
    }, (/*err*/
    ) => {
      const message = __('There was an error updating the Discourse topic.', 'wp-discourse');
      this.setState({
        busyUpdating: false,
        statusMessage: message
      });
      return null;
    });
  }
  render() {
    if (this.isAllowedPostType()) {
      const isPublished = this.state.published,
        forcePublish = this.state.forcePublish,
        pluginUnconfigured = pluginOptions.pluginUnconfigured;
      let actions;
      if (pluginUnconfigured) {
        actions = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
          className: 'wpdc-plugin-unconfigured',
          children: __("Before you can publish posts from WordPress to Discourse, you need to configure the plugin's Connection Settings tab.", 'discourse-integration')
        });
      } else if (!isPublished && !forcePublish) {
        actions = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
          className: 'wpdc-not-published',
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PublishingOptions, {
            handlePublishMethodChange: this.handlePublishMethodChange,
            publishingMethod: this.state.publishingMethod
          }), 'publish_post' === this.state.publishingMethod ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
            className: 'wpdc-publish-to-discourse',
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(CategorySelect, {
              category_id: this.state.publishPostCategory,
              handleCategoryChange: this.handleCategoryChange,
              discourseCategories: this.state.discourseCategories,
              categoryError: this.state.categoryError
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(TagTopic, {
              handleTagChange: this.handleTagChange,
              tags: this.state.topicTags,
              allowTags: this.state.allowTags,
              maxTags: this.state.maxTags
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PinTopic, {
              handlePinChange: this.handlePinChange,
              pinTopic: this.state.pinTopic,
              pinUntil: this.state.pinUntil
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PublishToDiscourse, {
              postStatus: this.state.postStatus,
              publishToDiscourse: this.state.publishToDiscourse,
              handleToBePublishedChange: this.handleToBePublishedChange,
              handlePublishChange: this.handlePublishChange,
              busy: this.state.busyPublishing
            })]
          }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
            className: 'wpdc-link-to-discourse',
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(LinkToDiscourseTopic, {
              busy: this.state.busyLinking,
              handleLinkTopicClick: this.handleLinkTopicClick
            })
          })]
        });
      } else if (!forcePublish) {
        actions = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
          className: 'wpdc-published-post',
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(UpdateDiscourseTopic, {
            published: this.state.published,
            busy: this.state.busyUpdating,
            handleUpdateChange: this.handleUpdateChange,
            forcePublish: this.state.forcePublish
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(UnlinkFromDiscourse, {
            published: this.state.published,
            handleUnlinkFromDiscourseChange: this.handleUnlinkFromDiscourseChange,
            busy: this.state.busyUnlinking,
            forcePublish: this.state.forcePublish
          })]
        });
      } else {
        actions = null;
      }
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(Fragment, {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PluginSidebarMoreMenuItem, {
          target: "discourse-sidebar",
          children: __('Discourse', 'wp-discourse')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PluginSidebar, {
          name: "discourse-sidebar",
          title: __('Discourse', 'wp-discourse'),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PanelBody, {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
              className: 'wpdc-sidebar',
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Notification, {
                published: this.state.published,
                forcePublish: this.state.forcePublish,
                publishingError: this.state.publishingError,
                discoursePermalink: this.state.discoursePermalink,
                statusMessage: this.state.statusMessage
              }), actions]
            })
          })
        })]
      });
    }
    return null;
  }
}
const HOC = compose([withSelect((select, {
  /*forceIsSaving*/
}) => {
  const {
    getCurrentPostId,
    getCurrentPost
  } = select('core/editor');
  return {
    postId: getCurrentPostId(),
    post: getCurrentPost()
  };
}), withDispatch(dispatch => {
  const {
    editPost
  } = dispatch('core/editor');
  return {
    setAutoPublishOverridden(value) {
      editPost({
        meta: {
          wpdc_auto_publish_overridden: value
        }
      });
    }
  };
})])(DiscourseSidebar);
registerPlugin('discourse-sidebar', {
  icon: iconEl,
  render: HOC
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map