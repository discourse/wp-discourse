/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/assertThisInitialized.js ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/getPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  module.exports["default"] = module.exports, module.exports.__esModule = true;
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inherits.js":
/*!*********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/inherits.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(/*! ./setPrototypeOf.js */ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js")["default"];

var assertThisInitialized = __webpack_require__(/*! ./assertThisInitialized.js */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/setPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  module.exports["default"] = module.exports, module.exports.__esModule = true;
  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  }

  return _typeof(obj);
}

module.exports = _typeof;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

/*jshint esversion: 6*/

/**
 * Internal block libraries.
 */
var __ = wp.i18n.__;
var _wp$editPost = wp.editPost,
    PluginSidebar = _wp$editPost.PluginSidebar,
    PluginSidebarMoreMenuItem = _wp$editPost.PluginSidebarMoreMenuItem;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    TextControl = _wp$components.TextControl;
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;
var withSelect = wp.data.withSelect;
var compose = wp.compose.compose;
var registerPlugin = wp.plugins.registerPlugin;
var el = wp.element.createElement; // See: https://wp.zacgordon.com/2017/12/07/how-to-add-custom-icons-to-gutenberg-editor-blocks-in-wordpress/

var iconEl = el('img', {
  width: 20,
  height: 20,
  src: pluginOptions.logo,
  class: 'wpdc-logo'
});
var buttonClass = 'components-button is-button is-default is-primary is-large wpdc-button';
var activeButtonClass = 'components-button is-button is-default is-primary is-large wpdc-button active';
var downArrow = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("svg", {
  className: 'components-panel__arrow',
  width: "24px",
  height: "24px",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  role: "img",
  "aria-hidden": "true",
  focusable: "false"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("g", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("path", {
  fill: 'none',
  d: 'M0,0h24v24H0V0z'
})), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("g", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("path", {
  d: 'M7.41,8.59L12,13.17l4.59-4.58L18,10l-6,6l-6-6L7.41,8.59z'
})));
var upArrow = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("svg", {
  className: 'components-panel__arrow',
  width: "24px",
  height: "24px",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  role: "img",
  "aria-hidden": "true",
  focusable: "false"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("g", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("path", {
  fill: 'none',
  d: 'M0,0h24v24H0V0z'
})), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("g", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("path", {
  d: 'M12,8l-6,6l1.41,1.41L12,10.83l4.59,4.58L18,14L12,8z'
})));
var tagsFilterRegExp = /[\/\?#\[\]@!\$&'\(\)\*\+,;=\.%\\`^\s|\{\}"<>]+/;

var Notification = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(Notification, _Component);

  var _super = _createSuper(Notification);

  function Notification(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, Notification);

    return _super.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(Notification, [{
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(ForcePublishMessage, {
        forcePublish: this.props.forcePublish,
        published: this.props.published
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(StatusMessage, {
        statusMessage: this.props.statusMessage
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(ErrorMessage, {
        publishingError: this.props.publishingError
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(DiscoursePermalink, {
        discoursePermalink: this.props.discoursePermalink
      }));
    }
  }]);

  return Notification;
}(Component);

var ForcePublishMessage = /*#__PURE__*/function (_Component2) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(ForcePublishMessage, _Component2);

  var _super2 = _createSuper(ForcePublishMessage);

  function ForcePublishMessage(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ForcePublishMessage);

    return _super2.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ForcePublishMessage, [{
    key: "render",
    value: function render() {
      if (this.props.forcePublish && !this.props.published) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
          className: 'wpdc-force-publish-message'
        }, __('The Force Publish option is enabled for your site. All posts published on WordPress will be automatically published to Discourse.', 'wp-discourse'));
      } else {
        return '';
      }
    }
  }]);

  return ForcePublishMessage;
}(Component);

var StatusMessage = /*#__PURE__*/function (_Component3) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(StatusMessage, _Component3);

  var _super3 = _createSuper(StatusMessage);

  function StatusMessage(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, StatusMessage);

    return _super3.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(StatusMessage, [{
    key: "render",
    value: function render() {
      var statusMessage = this.props.statusMessage;

      if (statusMessage) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-publishing-response success'
        }, statusMessage);
      }

      return '';
    }
  }]);

  return StatusMessage;
}(Component);

var ErrorMessage = /*#__PURE__*/function (_Component4) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(ErrorMessage, _Component4);

  var _super4 = _createSuper(ErrorMessage);

  function ErrorMessage(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ErrorMessage);

    return _super4.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ErrorMessage, [{
    key: "render",
    value: function render() {
      var publishingError = this.props.publishingError;

      if (publishingError) {
        var message;

        switch (publishingError) {
          case 'deleted_topic':
            message = __('Your post could not be published to Discourse. The associated Discourse topic may have been deleted. ' + 'Unlink the post so that it can be published again.', 'wp-discourse');
            break;

          case 'Not Found':
            message = __('Your post could not be updated on Discourse. The associated Discourse topic may have been deleted. ' + 'Unlink the post so that it can be published again.', 'wp-discourse');
            break;

          case 'queued_topic':
            message = __('Your post has been added to the Discourse approval queue. When it has been approved, you will need to link it to Discourse by' + 'selecting the \'Link to Existing Topic\' option.', 'wp-discourse');
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

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-publishing-response error'
        }, message);
      }

      return '';
    }
  }]);

  return ErrorMessage;
}(Component);

var DiscoursePermalink = /*#__PURE__*/function (_Component5) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(DiscoursePermalink, _Component5);

  var _super5 = _createSuper(DiscoursePermalink);

  function DiscoursePermalink(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, DiscoursePermalink);

    return _super5.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(DiscoursePermalink, [{
    key: "render",
    value: function render() {
      if (this.props.discoursePermalink) {
        var permalink = encodeURI(this.props.discoursePermalink);
        var link = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("a", {
          href: permalink,
          className: 'wpdc-permalink-link',
          target: '_blank',
          rel: 'noreferrer noopener'
        }, permalink);
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-permalink'
        }, __('Your post is linked with', 'wp-discourse'), " ", link);
      }

      return '';
    }
  }]);

  return DiscoursePermalink;
}(Component);

var PublishingOptions = /*#__PURE__*/function (_Component6) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(PublishingOptions, _Component6);

  var _super6 = _createSuper(PublishingOptions);

  function PublishingOptions(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, PublishingOptions);

    _this = _super6.call(this, props);
    _this.handleChange = _this.handleChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(PublishingOptions, [{
    key: "handleChange",
    value: function handleChange(e) {
      this.props.handlePublishMethodChange(e.target.value);
    }
  }, {
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: 'wpdc-publishing-options'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
        className: 'wpdc-sidebar-title'
      }, __('Publishing Options', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("label", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
        type: "radio",
        name: "wpdc_publish_options",
        value: "publish_post",
        checked: 'publish_post' === this.props.publishingMethod,
        onChange: this.handleChange
      }), __('New Topic', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("label", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
        type: "radio",
        name: "wpdc_publish_options",
        value: "link_post",
        checked: 'link_post' === this.props.publishingMethod,
        onChange: this.handleChange
      }), __('Link to Existing Topic', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("hr", {
        className: 'wpdc-sidebar-hr'
      }));
    }
  }]);

  return PublishingOptions;
}(Component);

var PublishToDiscourse = /*#__PURE__*/function (_Component7) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(PublishToDiscourse, _Component7);

  var _super7 = _createSuper(PublishToDiscourse);

  function PublishToDiscourse(props) {
    var _this2;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, PublishToDiscourse);

    _this2 = _super7.call(this, props);
    _this2.handleToBePublishedChange = _this2.handleToBePublishedChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this2));
    _this2.handlePublishChange = _this2.handlePublishChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this2));
    return _this2;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(PublishToDiscourse, [{
    key: "handleToBePublishedChange",
    value: function handleToBePublishedChange(e) {
      this.props.handleToBePublishedChange(e.target.checked);
    }
  }, {
    key: "handlePublishChange",
    value: function handlePublishChange(e) {
      this.props.handlePublishChange(e);
    }
  }, {
    key: "render",
    value: function render() {
      var publishToDiscourse = this.props.publishToDiscourse,
          publishedOnWordPress = 'publish' === this.props.postStatus;

      if (!publishedOnWordPress) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-component-panel-body'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
          className: 'wpdc-sidebar-title'
        }, __('Publish to Discourse', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-publish-topic'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
          type: "checkBox",
          className: 'wpdc-publish-topic-checkbox',
          checked: publishToDiscourse,
          onChange: this.handleToBePublishedChange
        }), __('Publish', 'wp-discourse'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
          className: 'wpdc-info'
        }, __('Automatically publish the post to Discourse when it is published on WordPress.', 'wp-discourse'))));
      } else {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-component-panel-body'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
          className: 'wpdc-sidebar-title'
        }, __('Publish to Discourse', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
          className: this.props.busy ? activeButtonClass : buttonClass,
          onClick: this.handlePublishChange
        }, __('Publish to Discourse', 'wp-discourse')));
      }
    }
  }]);

  return PublishToDiscourse;
}(Component);

var CategorySelect = /*#__PURE__*/function (_Component8) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(CategorySelect, _Component8);

  var _super8 = _createSuper(CategorySelect);

  function CategorySelect(props) {
    var _this3;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, CategorySelect);

    _this3 = _super8.call(this, props);
    _this3.handleChange = _this3.handleChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this3));
    return _this3;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(CategorySelect, [{
    key: "handleChange",
    value: function handleChange(e) {
      this.props.handleCategoryChange(e.target.value);
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      if (this.props.discourseCategories) {
        var cats = Object.values(this.props.discourseCategories);
        var options = cats.map(function (cat) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("option", {
            value: cat.id,
            selected: parseInt(_this4.props.category_id, 10) === parseInt(cat.id, 10)
          }, cat.name);
        });
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-category-select wpdc-component-panel-body'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
          className: 'wpdc-sidebar-title'
        }, __('Category', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("select", {
          onChange: this.handleChange,
          className: 'widefat'
        }, options), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("hr", {
          className: 'wpdc-sidebar-hr'
        }));
      } else if (this.props.categoryError) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-api-error error'
        }, __('There was an error returning the category list from Discourse.', 'discourse-integration'));
      } else {
        return null;
      }
    }
  }]);

  return CategorySelect;
}(Component);

var LinkToDiscourseTopic = /*#__PURE__*/function (_Component9) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(LinkToDiscourseTopic, _Component9);

  var _super9 = _createSuper(LinkToDiscourseTopic);

  function LinkToDiscourseTopic(props) {
    var _this5;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, LinkToDiscourseTopic);

    _this5 = _super9.call(this, props);
    _this5.state = {
      isBusy: false,
      topicUrl: null
    };
    _this5.handleChange = _this5.handleChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this5));
    _this5.handleClick = _this5.handleClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this5));
    return _this5;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(LinkToDiscourseTopic, [{
    key: "handleChange",
    value: function handleChange(e) {
      this.setState({
        topicUrl: e.target.value
      });
    }
  }, {
    key: "handleClick",
    value: function handleClick(e) {
      this.props.handleLinkTopicClick(this.state.topicUrl);
    }
  }, {
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: 'wpdc-link-post wpdc-component-panel-body'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
        className: 'wpdc-sidebar-title'
      }, __('Topic URL', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
        type: "url",
        className: 'widefat wpdc-topic-url-input',
        onChange: this.handleChange,
        value: this.state.topicUrl
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        className: this.props.busy ? activeButtonClass : buttonClass,
        onClick: this.handleClick
      }, __('Link With Discourse', 'wp-discourse')));
    }
  }]);

  return LinkToDiscourseTopic;
}(Component);

var UnlinkFromDiscourse = /*#__PURE__*/function (_Component10) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(UnlinkFromDiscourse, _Component10);

  var _super10 = _createSuper(UnlinkFromDiscourse);

  function UnlinkFromDiscourse(props) {
    var _this6;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, UnlinkFromDiscourse);

    _this6 = _super10.call(this, props);
    _this6.state = {
      showPanel: false
    };
    _this6.handleClick = _this6.handleClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this6));
    _this6.togglePanel = _this6.togglePanel.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this6));
    return _this6;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(UnlinkFromDiscourse, [{
    key: "handleClick",
    value: function handleClick(e) {
      this.props.handleUnlinkFromDiscourseChange(e);
    }
  }, {
    key: "togglePanel",
    value: function togglePanel() {
      this.setState({
        showPanel: !this.state.showPanel
      });
    }
  }, {
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: 'wpdc-component-panel-body'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
        className: 'wpdc-panel-section-title'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        type: "button",
        "aria-expanded": "false",
        className: 'components-button components-panel__body-toggle',
        onClick: this.togglePanel
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
        "aria-hidden": "true"
      }, this.state.showPanel ? upArrow : downArrow), __('Unlink From Discourse', 'wp-discourse'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: !this.state.showPanel ? 'hidden' : ''
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
        className: 'wpdc-info'
      }, __('Unlinking the post from Discourse will remove all Discourse metadata from the post.', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        className: this.props.busy ? activeButtonClass : buttonClass,
        onClick: this.handleClick
      }, __('Unlink Post', 'wp-discourse'))));
    }
  }]);

  return UnlinkFromDiscourse;
}(Component);

var UpdateDiscourseTopic = /*#__PURE__*/function (_Component11) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(UpdateDiscourseTopic, _Component11);

  var _super11 = _createSuper(UpdateDiscourseTopic);

  function UpdateDiscourseTopic(props) {
    var _this7;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, UpdateDiscourseTopic);

    _this7 = _super11.call(this, props);
    _this7.state = {
      showPanel: false
    };
    _this7.handleClick = _this7.handleClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this7));
    _this7.togglePanel = _this7.togglePanel.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this7));
    return _this7;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(UpdateDiscourseTopic, [{
    key: "togglePanel",
    value: function togglePanel() {
      this.setState({
        showPanel: !this.state.showPanel
      });
    }
  }, {
    key: "handleClick",
    value: function handleClick(e) {
      this.props.handleUpdateChange(e);
    }
  }, {
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: 'wpdc-component-panel-body'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
        className: 'wpdc-panel-section-title'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        type: "button",
        "aria-expanded": "false",
        className: 'components-button components-panel__body-toggle',
        onClick: this.togglePanel
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
        "aria-hidden": "true"
      }, this.state.showPanel ? upArrow : downArrow), __('Update Discourse Topic', 'wp-discourse'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: !this.state.showPanel ? 'hidden' : ''
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
        className: 'wpdc-info'
      }, __('Update the Discourse topic to the lastest saved version of the post.', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        className: this.props.busy ? activeButtonClass : buttonClass,
        onClick: this.handleClick
      }, __('Update Topic', 'wp-discourse'))));
    }
  }]);

  return UpdateDiscourseTopic;
}(Component);

var TagTopic = /*#__PURE__*/function (_Component12) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(TagTopic, _Component12);

  var _super12 = _createSuper(TagTopic);

  function TagTopic(props) {
    var _this8;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, TagTopic);

    _this8 = _super12.call(this, props);
    _this8.state = {
      chosenTags: _this8.props.tags,
      inputContent: '',
      inputLength: 1,
      maxTagsExceeded: false
    };
    _this8.handleKeyPress = _this8.handleKeyPress.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this8));
    _this8.handleChange = _this8.handleChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this8));
    _this8.handleClick = _this8.handleClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this8));
    _this8.focusInput = _this8.focusInput.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this8));
    return _this8;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(TagTopic, [{
    key: "focusInput",
    value: function focusInput(e) {
      this.tagInput.focus();
    }
  }, {
    key: "handleChange",
    value: function handleChange(e) {
      var val = e.target.value;
      this.setState({
        inputContent: ',' === val ? '' : val,
        inputLength: val.length === 0 ? 1 : val.length
      });
    }
  }, {
    key: "handleKeyPress",
    value: function handleKeyPress(e) {
      var _this9 = this;

      var keyVal = e.key,
          val = e.target.value;

      if ('Enter' === keyVal || ',' === keyVal) {
        var currentChoices = this.state.chosenTags;

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
          }, function () {
            _this9.props.handleTagChange(currentChoices);
          });
        } else {
          this.setState({
            inputContent: ''
          });
        }
      }
    } // see discourse/lib/discourse_tagging.rb#clean_tag

  }, {
    key: "cleanTag",
    value: function cleanTag(val) {
      val = val.trim(); // remove surrounding whitespace

      val = val.replace(/ /g, '-'); // replace whitespace with hyphen

      val = val.replace(/(-)\1+/g, '-'); // remove duplicate hyphens

      val = val.replace(new RegExp(tagsFilterRegExp, 'g'), ''); // remove special characters

      return val;
    }
  }, {
    key: "handleClick",
    value: function handleClick(key) {
      var _this10 = this;

      var tags = this.state.chosenTags,
          index = tags.indexOf(key);

      if (index > -1) {
        tags.splice(index, 1);
        this.setState({
          chosenTags: tags,
          maxTagsExceeded: false
        }, function () {
          _this10.props.handleTagChange(tags);
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this11 = this;

      if (this.props.allowTags) {
        var maxTagsNotice = this.state.maxTagsExceeded ? __('You have exceeded the maximum number of allowed tags for your site. Remove a tag to add more.', 'wp-discourse') : '';
        var tagDisplay = TagTopic.sanitizeArray(this.state.chosenTags);
        tagDisplay = tagDisplay.map(function (tag, index) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
            className: 'components-form-token-field__token',
            key: tag
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
            className: 'components-form-token-field__token-text'
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
            className: 'screen-reader-text'
          }, tag), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
            "aria-hidden": "true"
          }, tag)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
            type: "button",
            "aria-label": "Remove Tag",
            className: 'components-button components-icon-button components-form-token-field__remove-token',
            onClick: _this11.handleClick.bind(_this11, tag),
            key: tag
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("svg", {
            "aria-hidden": "true",
            role: "img",
            focusable: "false",
            className: 'dashicon dashicons-dismiss',
            xmlns: "http://www.w3.org/2000/svg",
            width: "20",
            height: "20",
            viewBox: "0 0 20 20"
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("path", {
            d: 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm5 11l-3-3 3-3-2-2-3 3-3-3-2 2 3 3-3 3 2 2 3-3 3 3z'
          }))));
        });
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-component-panel-body'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
          className: 'wpdc-sidebar-title'
        }, __('Tags', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'components-form-token-field__input-container',
          onClick: this.focusInput
        }, tagDisplay, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
          type: 'text',
          size: this.state.inputLength,
          className: 'components-form-token-field__input',
          onChange: this.handleChange,
          onKeyPress: this.handleKeyPress,
          value: this.state.inputContent,
          ref: function ref(input) {
            _this11.tagInput = input;
          }
        })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: this.state.maxTagsExceeded ? 'wpdc-info' : ''
        }, maxTagsNotice), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("hr", {
          className: 'wpdc-sidebar-hr'
        }));
      } else {
        return null;
      }
    }
  }], [{
    key: "sanitizeArray",
    value: function sanitizeArray(arr) {
      arr = arr.sort().reduce(function (accumulator, current) {
        var length = accumulator.length;

        if ((0 === length || accumulator[length - 1] !== current) && current.trim() !== '') {
          accumulator.push(current);
        }

        return accumulator;
      }, []);
      return arr;
    }
  }]);

  return TagTopic;
}(Component);

var PinTopic = /*#__PURE__*/function (_Component13) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(PinTopic, _Component13);

  var _super13 = _createSuper(PinTopic);

  function PinTopic(props) {
    var _this12;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, PinTopic);

    _this12 = _super13.call(this, props);
    _this12.state = {
      pinTopic: _this12.props.pinTopic,
      pinUntil: _this12.props.pinUntil
    };
    _this12.handleUpdateDate = _this12.handleUpdateDate.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this12));
    _this12.handleToBePinnedChange = _this12.handleToBePinnedChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this12));
    return _this12;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(PinTopic, [{
    key: "handleUpdateDate",
    value: function handleUpdateDate(e) {
      this.setState({
        pinUntil: e.target.value
      });
      this.props.handlePinChange(this.state.pinTopic, e.target.value);
    }
  }, {
    key: "handleToBePinnedChange",
    value: function handleToBePinnedChange(e) {
      this.setState({
        pinTopic: e.target.checked
      });
      this.props.handlePinChange(e.target.checked, this.state.pinUntil);
    }
  }, {
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: 'wpdc-component-panel-body'
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h2", {
        className: 'wpdc-sidebar-title'
      }, __('Pin Topic', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("label", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
        type: 'checkbox',
        onChange: this.handleToBePinnedChange,
        checked: this.state.pinTopic
      }), __('Pin Discourse Topic', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("label", {
        className: 'wpdc-pin-until-input'
      }, __('Pin Until', 'wp-discourse'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
        type: 'date',
        className: 'widefat',
        onChange: this.handleUpdateDate,
        value: this.state.pinUntil
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("hr", {
        className: 'wpdc-sidebar-hr'
      }));
    }
  }]);

  return PinTopic;
}(Component);

var DiscourseSidebar = /*#__PURE__*/function (_Component14) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(DiscourseSidebar, _Component14);

  var _super14 = _createSuper(DiscourseSidebar);

  function DiscourseSidebar(props) {
    var _this13;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, DiscourseSidebar);

    _this13 = _super14.call(this, props);
    _this13.state = _this13.initializePostState(_this13.props.post);

    _this13.getDiscourseCategories();

    _this13.handleToBePublishedChange = _this13.handleToBePublishedChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handlePublishChange = _this13.handlePublishChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handleCategoryChange = _this13.handleCategoryChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handleTagChange = _this13.handleTagChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handleUnlinkFromDiscourseChange = _this13.handleUnlinkFromDiscourseChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handlePublishMethodChange = _this13.handlePublishMethodChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handleUpdateChange = _this13.handleUpdateChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handleLinkTopicClick = _this13.handleLinkTopicClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    _this13.handlePinChange = _this13.handlePinChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0___default()(_this13));
    return _this13;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(DiscourseSidebar, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      if (this.isAllowedPostType()) {
        if (this.publishedPostHasChanged(prevProps.post, this.props.post)) {
          this.updatePostState(this.props.post);
        }
      }
    }
  }, {
    key: "publishedPostHasChanged",
    value: function publishedPostHasChanged(prev, post) {
      if (!prev || !post || !prev.meta || !post.meta) return false; // We don't refresh state if post is not yet published

      if ([post.status, prev.status].every(function (s) {
        return s !== 'publish';
      })) return false; // We always refresh state on a status change

      if (post.status !== prev.status) return true; // We refresh state on publishing error or linked post change

      return ['discourse_post_id', 'wpdc_publishing_response', 'wpdc_publishing_error'].some(function (attr) {
        return post.meta[attr] !== prev.meta[attr];
      });
    }
  }, {
    key: "initializePostState",
    value: function initializePostState(post) {
      if (!this.isAllowedPostType()) {
        return {};
      }

      var state = {
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
  }, {
    key: "updatePostState",
    value: function updatePostState(post) {
      this.setState(this.buildPostState(post));
    }
  }, {
    key: "buildPostState",
    value: function buildPostState(post) {
      if (!post || !post.meta) return {};
      var meta = post.meta;
      var topicTags = typeof meta.wpdc_topic_tags === 'string' ? meta.wpdc_topic_tags.split(',') : [];
      var postState = {
        publishToDiscourse: this.determinePublishToDiscourse(meta),
        published: meta.discourse_post_id > 0,
        postStatus: post.status,
        topicTags: topicTags,
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
  }, {
    key: "determinePublishToDiscourse",
    value: function determinePublishToDiscourse(meta) {
      var autoPublish = pluginOptions.autoPublish,
          autoPublishOverridden = 1 === parseInt(meta.wpdc_auto_publish_overridden, 10);
      var publishToDiscourse;

      if (['deleted_topic', 'queued_topic'].includes(meta.wpdc_publishing_error)) {
        publishToDiscourse = false;
      } else if (autoPublish && !autoPublishOverridden) {
        publishToDiscourse = true;
      } else {
        publishToDiscourse = 1 === parseInt(meta.wpdc_publish_to_discourse, 10);
      }

      return publishToDiscourse;
    }
  }, {
    key: "getDiscourseCategories",
    value: function getDiscourseCategories() {
      var _this14 = this;

      if (!pluginOptions.pluginUnconfigured) {
        wp.apiRequest({
          path: '/wp-discourse/v1/get-discourse-categories',
          method: 'GET',
          data: {
            get_categories_nonce: pluginOptions.get_categories_nonce,
            id: this.props.postId
          }
        }).then(function (data) {
          _this14.setState({
            discourseCategories: data
          });
        }, function (err) {
          _this14.setState({
            categoryError: true
          });
        });
      }
    }
  }, {
    key: "isAllowedPostType",
    value: function isAllowedPostType() {
      return pluginOptions.allowedPostTypes.indexOf(this.props.post.type) >= 0;
    }
  }, {
    key: "handlePublishMethodChange",
    value: function handlePublishMethodChange(publishingMethod) {
      this.setState({
        publishingMethod: publishingMethod
      });
    }
  }, {
    key: "handleToBePublishedChange",
    value: function handleToBePublishedChange(publishToDiscourse) {
      var _this15 = this;

      this.setState({
        publishToDiscourse: publishToDiscourse,
        statusMessage: ''
      }, function () {
        wp.apiRequest({
          path: '/wp-discourse/v1/set-publish-meta',
          method: 'POST',
          data: {
            set_publish_meta_nonce: pluginOptions.set_publish_meta_nonce,
            id: _this15.props.postId,
            publish_to_discourse: _this15.state.publishToDiscourse ? 1 : 0
          }
        }).then(function (data) {
          return null;
        }, function (err) {
          return null;
        });
      });
    }
  }, {
    key: "handleCategoryChange",
    value: function handleCategoryChange(categoryId) {
      var _this16 = this;

      this.setState({
        publishPostCategory: categoryId
      }, function () {
        wp.apiRequest({
          path: '/wp-discourse/v1/set-category-meta',
          method: 'POST',
          data: {
            set_category_meta_nonce: pluginOptions.set_category_meta_nonce,
            id: _this16.props.postId,
            publish_post_category: categoryId
          }
        }).then(function (data) {
          return null;
        }, function (err) {
          return null;
        });
      });
    }
  }, {
    key: "handlePinChange",
    value: function handlePinChange(pinTopic, pinUntil) {
      var _this17 = this;

      this.setState({
        pinTopic: pinTopic,
        pinUntil: pinUntil
      }, function () {
        wp.apiRequest({
          path: '/wp-discourse/v1/set-pin-meta',
          method: 'Post',
          data: {
            set_pin_meta_nonce: pluginOptions.set_pin_meta_nonce,
            id: _this17.props.postId,
            wpdc_pin_topic: pinTopic ? 1 : 0,
            wpdc_pin_until: pinUntil
          }
        }).then(function (data) {
          return null;
        }, function (err) {
          return null;
        });
      });
    }
  }, {
    key: "handleTagChange",
    value: function handleTagChange(tags) {
      var _this18 = this;

      this.setState({
        topicTags: tags
      }, function () {
        var tagString = tags.join(',');
        wp.apiRequest({
          path: '/wp-discourse/v1/set-tag-meta',
          method: 'POST',
          data: {
            set_tag_meta_nonce: pluginOptions.set_tag_meta_nonce,
            id: _this18.props.postId,
            wpdc_topic_tags: tagString
          }
        }).then(function (data) {
          return null;
        }, function (err) {
          return null;
        });
      });
    }
  }, {
    key: "handleLinkTopicClick",
    value: function handleLinkTopicClick(topicUrl) {
      var _this19 = this;

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
      }).then(function (data) {
        _this19.setState({
          busyLinking: false
        });

        if (data.discourse_permalink) {
          _this19.setState({
            published: true,
            discoursePermalink: data.discourse_permalink,
            publishingError: null
          });
        } else {
          _this19.setState({
            publishingError: __('There has been an error linking your post with Discourse.', 'wp-discourse')
          });
        }

        return null;
      }, function (err) {
        var message = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : __('There has been an error linking your post with Discourse.', 'wp-discourse');

        _this19.setState({
          busyLinking: false,
          published: false,
          publishingError: message
        });

        return null;
      });
    }
  }, {
    key: "handleUnlinkFromDiscourseChange",
    value: function handleUnlinkFromDiscourseChange(e) {
      var _this20 = this;

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
      }).then(function (data) {
        _this20.setState({
          busyUnlinking: false,
          published: false,
          publishingMethod: 'link_post',
          discoursePermalink: null,
          statusMessage: __('Your post has been unlinked from Discourse.', 'wp-discourse')
        });

        return null;
      }, function (err) {
        return null;
      });
    }
  }, {
    key: "handlePublishChange",
    value: function handlePublishChange(e) {
      var _this21 = this;

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
      }).then(function (data) {
        var success = 'success' === data.publish_response;

        _this21.setState({
          busyPublishing: false,
          published: success,
          publishingError: success ? null : data.publish_response,
          publishingMethod: data.publish_response = 'Unprocessable Entity' ? 'link_post' : undefined,
          discoursePermalink: data.discourse_permalink
        });

        return null;
      }, function (err) {
        var message = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : __('There has been an error linking your post with Discourse.', 'wp-discourse');

        _this21.setState({
          busyPublishing: false,
          published: false,
          publishingError: message
        });

        return null;
      });
    }
  }, {
    key: "handleUpdateChange",
    value: function handleUpdateChange(e) {
      var _this22 = this;

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
      }).then(function (data) {
        var response = data.update_response,
            success = 'success' === response;
        var message;

        if (success) {
          message = __('The Discourse topic has been updated!', 'wp-discourse');
        }

        _this22.setState({
          busyUpdating: false,
          statusMessage: message,
          publishingError: success ? null : data.update_response
        });

        return null;
      }, function (err) {
        var message = __('There was an error updating the Discourse topic.', 'wp-discourse');

        _this22.setState({
          busyUpdating: false,
          statusMessage: message
        });

        return null;
      });
    }
  }, {
    key: "render",
    value: function render() {
      if (this.isAllowedPostType()) {
        var isPublished = this.state.published,
            forcePublish = this.state.forcePublish,
            pluginUnconfigured = pluginOptions.pluginUnconfigured;
        var actions;

        if (pluginUnconfigured) {
          actions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: 'wpdc-plugin-unconfigured'
          }, __("Before you can publish posts from WordPress to Discourse, you need to configure the plugin's Connection Settings tab.", 'discourse-integration'));
        } else if (!isPublished && !forcePublish) {
          actions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: 'wpdc-not-published'
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PublishingOptions, {
            handlePublishMethodChange: this.handlePublishMethodChange,
            publishingMethod: this.state.publishingMethod
          }), 'publish_post' === this.state.publishingMethod ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: 'wpdc-publish-to-discourse'
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(CategorySelect, {
            category_id: this.state.publishPostCategory,
            handleCategoryChange: this.handleCategoryChange,
            discourseCategories: this.state.discourseCategories,
            categoryError: this.state.categoryError
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(TagTopic, {
            handleTagChange: this.handleTagChange,
            tags: this.state.topicTags,
            allowTags: this.state.allowTags,
            maxTags: this.state.maxTags
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PinTopic, {
            handlePinChange: this.handlePinChange,
            pinTopic: this.state.pinTopic,
            pinUntil: this.state.pinUntil
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PublishToDiscourse, {
            postStatus: this.state.postStatus,
            publishToDiscourse: this.state.publishToDiscourse,
            handleToBePublishedChange: this.handleToBePublishedChange,
            handlePublishChange: this.handlePublishChange,
            busy: this.state.busyPublishing
          })) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: 'wpdc-link-to-discourse'
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(LinkToDiscourseTopic, {
            busy: this.state.busyLinking,
            handleLinkTopicClick: this.handleLinkTopicClick
          })));
        } else if (!forcePublish) {
          actions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: 'wpdc-published-post'
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(UpdateDiscourseTopic, {
            published: this.state.published,
            busy: this.state.busyUpdating,
            handleUpdateChange: this.handleUpdateChange,
            forcePublish: this.state.forcePublish
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(UnlinkFromDiscourse, {
            published: this.state.published,
            handleUnlinkFromDiscourseChange: this.handleUnlinkFromDiscourseChange,
            busy: this.state.busyUnlinking,
            forcePublish: this.state.forcePublish
          }));
        } else {
          actions = null;
        }

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(Fragment, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PluginSidebarMoreMenuItem, {
          target: "discourse-sidebar"
        }, __('Discourse', 'wp-discourse')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PluginSidebar, {
          name: "discourse-sidebar",
          title: __('Discourse', 'wp-discourse')
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: 'wpdc-sidebar'
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(Notification, {
          published: this.state.published,
          forcePublish: this.state.forcePublish,
          publishingError: this.state.publishingError,
          discoursePermalink: this.state.discoursePermalink,
          statusMessage: this.state.statusMessage
        }), actions))));
      } else {
        return null;
      }
    }
  }]);

  return DiscourseSidebar;
}(Component);

var HOC = withSelect(function (select, _ref) {
  var forceIsSaving = _ref.forceIsSaving;

  var _select = select('core/editor'),
      getCurrentPostId = _select.getCurrentPostId,
      getCurrentPost = _select.getCurrentPost;

  return {
    postId: getCurrentPostId(),
    post: getCurrentPost()
  };
})(DiscourseSidebar);
registerPlugin('discourse-sidebar', {
  icon: iconEl,
  render: HOC
});

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.js.map