/**
 * Internal block libraries
 */

const {__} = wp.i18n;

const {
    PluginSidebar,
    PluginSidebarMoreMenuItem
} = wp.editPost;

const {
    PanelBody,
    TextControl
} = wp.components;

const {
    Component,
    Fragment
} = wp.element;

const {withSelect} = wp.data;

const {compose} = wp.compose;

const {registerPlugin} = wp.plugins;

const el = wp.element.createElement;

// See: https://wp.zacgordon.com/2017/12/07/how-to-add-custom-icons-to-gutenberg-editor-blocks-in-wordpress/
const iconEl = el('img', {
    width: 20,
    height: 20,
    src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTExIDc5LjE1ODMyNSwgMjAxNS8wOS8xMC0wMToxMDoyMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUYxNjlGNkY3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUYxNjlGNzA3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRjE2OUY2RDc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRjE2OUY2RTc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pq7th6IAAAP8SURBVHjalFbbT5tlHH56grKWnicw5ijdAjNGg5CxbPGIM7qx7cIlxihxd7oLr/wHlpgsu/FWjbdeeOWFwUQixmW6GGbKpDKcgqhj7AA90CM9t/j8Xr+vKZW08Gue0O/jfZ/n/R3fGg73+dHE2ogzxEvEU4SPMBJxYon4kZjUnnc0QxOBD4j3iX40txjxKfEhUdqNwJPE58SwWmAwoFQqoVQsolqtwmgyoa2tDWazGVtbW/X7FokJYrb+pbGB/DliTsiNRiPyuRw2YjEY+HF7fejqOQCHw4FcNotoOKyEZZ1mg0SQeKWe0Fz3PUBck3cGCepGDE6XG8dOnIQ/cBgutwcmnrpcLiEaieB2KIRfgj+jnd54fT5UKhWdZ1oTW2oUmFTkDEl8Y4OkAbx69jwOPn5IhaO76zH0dHfB7Xaj3WpFMpXGt1Pf4KOrV7Fy9x/4+wMUL+tcX2sitRxckkQJeTIRRy9J35h4B06nEz6vFyPDQ/D3HYKJ8W+0UGgeFyfexh93fqeIv94TKZCPdYH7RK/EVBJ54c23MHD0CXXiU2MvorPT3rSM7q7cw8ljo8xZFr79+xUH7SFxUDL0gpDLm81MBkcGj6qY2237cOrl1uRi4t3lK1dQYojy+Zz++gAxJgJj8iQlJyHo6e1VZTgy/Aw67a3JdXvt9GmcePZ5hjihSlszJTAg38QtIXY4nHC5nAgE+rEX28fED42MwGazq/LVS1cEOvQn8UKEfCy7Dmv7ngTyhbyKv4coFAr6a58IqNqShimyW1OpJOx2G/ZqaZatgRxWelKt1ippq9aGErdKpYzVlRWeprBngYeP1lApVxSMhhptZNuosDGpfy0t4vr31+rruaWtcWys3n9AL5LIpFOwWCz6v37bJmC1dnBBGqG5ORRK5V2RS1hv3gyqA/29/CcS8Q20tdfyN10/Kv5rdYZq9Pgoq6J1ktPpDG78NIP1cASRSBi/3rrFsWJRHKyYZS6Z2SZQZOzFi7Pj402Js9kcVu6tYmHhDuLJJDY3M5ia/Arh9Udwe7z6GL/cOOwQjUVxZvwchoaeRjyRxPztBczOztKzCr06rhovzW6PcYTH2VClYkkNuh++m8Yyc+fkINTIZ4gv/icgwy3HeXLp3fcQDM5ifW1dzReLxYwjA4Po4wiRNSazCSkKPFhdxfLiIkOVgsvjUZVIgRSpXq+/0b7k3wvyINlPcGMkGoHD3qlq2sLulubLMgxym0kIhahYLCgPbDabGt/ayRPabJvf6cJRLS4bBI2mSCgk1SKTRkaCsdOoiDVy+QFwUYZr443Wvat6JImcXC6f+tGinfYT4rOdtsnqKSkMfWS0MIOGtEZ8g7jebMO/AgwANr2XXAf8LaoAAAAASUVORK5CYII="
});

// Todo: remove unnecessary class names
const buttonClass = 'components-button is-button is-default is-primary is-large wpdc-button';
const activeButtonClass = 'components-button is-button is-default is-primary is-large wpdc-button active';
const downArrow = (<svg
    className={"components-panel__arrow"} width="24px" height="24px" viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
    <g>
        <path fill={"none"} d={"M0,0h24v24H0V0z"}/>
    </g>
    <g>
        <path d={"M7.41,8.59L12,13.17l4.59-4.58L18,10l-6,6l-6-6L7.41,8.59z"}/>
    </g>
</svg>);

const upArrow = (<svg
    className={"components-panel__arrow"} width="24px" height="24px" viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false">
    <g>
        <path fill={"none"} d={"M0,0h24v24H0V0z"}/>
    </g>
    <g>
        <path d={"M12,8l-6,6l1.41,1.41L12,10.83l4.59,4.58L18,14L12,8z"}/>
    </g>
</svg>);

class PublishingOptions extends Component {
    constructor(props) {
        super(props);

        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(e) {
        this.props.handlePublishMethodChange(e.target.value)
    }

    render() {
        if (!this.props.published) {
            return (
                <div className={'wpdc-publishing-options'}>
                    <h2 className={'wpdc-sidebar-title'}>{__('Publishing Options', 'wp-discourse')}</h2>
                    <label>
                        <input type="radio" name="wpdc_publish_options" value="publish_post"
                               checked={this.props.publishingMethod === 'publish_post'}
                               onChange={this.handleChange}/>
                        New Topic
                    </label>
                    <br/>
                    <label>
                        <input type="radio" name="wpdc_publish_options" value="link_post"
                               checked={this.props.publishingMethod === 'link_post'}
                               onChange={this.handleChange}/>
                        Link to Existing Topic
                    </label>
                </div>
            );
        } else {
            return '';
        }
    }
}

class PublishToDiscourseCheckBox extends Component {
    constructor(props) {
        super(props);

        this.state = {info: PublishToDiscourseCheckBox.publishingMessage(this.props.publish_to_discourse)};
        this.handleChange = this.handleChange.bind(this);
    }

    // Todo: this seems awkward
    static publishingMessage(publishToDiscourse) {
        return publishToDiscourse ? __('Your post will be published to Discourse when it is published or updated on WordPress.', 'wp-discourse') : '';
    }

    handleChange(e) {
        this.setState({info: PublishToDiscourseCheckBox.publishingMessage(e.target.checked)});
        this.props.handlePublishChange(e.target.checked);
    }

    render() {
        const publishToDiscourse = this.props.publishToDiscourse;

        if (!this.props.published && this.props.publishingMethod === 'publish_post') {
            return (
                <div className={'wpdc-publish-topic'}>

                    <input type="checkBox" className={'wpdc-publish-topic-checkbox'}
                           checked={publishToDiscourse} onChange={this.handleChange}/>
                    {__('Publish Post to Discourse', 'wp-discourse')}
                    <span className={'wpdc-publishing-method'}>{this.state.info}</span>
                </div>
            );
        } else {
            return '';
        }
    }
}

class DiscourseCategorySelect extends Component {
    constructor(props) {
        super(props);

        this.state = {
            discourseCategories: null,
        };

        this.handleChange = this.handleChange.bind(this);

        wp.apiFetch({path: 'wp-discourse/v1/get-discourse-categories', method: 'GET'}).then(
            (data) => {
                this.setState({
                    discourseCategories: data
                });
            },
            (err) => {
                return err;
            }
        );
    }

    handleChange(e) {
        this.props.handleCategoryChange(e.target.value);
    }

    render() {
        if (!this.props.published && this.props.publishingMethod === 'publish_post' && this.state.discourseCategories) {
            const cats = Object.values(this.state.discourseCategories);
            const options = cats.map((cat) =>
                <option value={cat.id}
                        selected={parseInt(this.props.category_id, 10) === parseInt(cat.id, 10)}>{cat.name}</option>
            );
            return (
                <div className={'wpdc-category-select'}>
                    {__('Publish Category:', 'wp-discourse')}
                    <select onChange={this.handleChange} className={'widefat'}>
                        {options}
                    </select>
                </div>
            );
        } else {
            return '';
        }
    }
}

class LinkToDiscourseTopic extends Component {
    constructor(props) {
        super(props);

        this.state = {
            isBusy: false,
            topicUrl: null,
        };

        this.handleChange = this.handleChange.bind(this);
        this.handleClick = this.handleClick.bind(this);
    }

    handleChange(e) {
        this.setState({topicUrl: e.target.value})
    }

    handleClick(e) {
        this.props.handleLinkTopicClick(this.state.topicUrl);
    }

    render() {
        if (!this.props.published && this.props.publishingMethod === 'link_post') {
            return (
                <div className="wpdc-link-post">
                    <label>
                        {__('Topic URL:', 'wp-discourse')}
                        <input
                            type="url"
                            className={'widefat wpdc-topic-url-input'}
                            onChange={this.handleChange}
                            value={this.state.linkedTopicUrl}
                        />

                        <button className={this.props.busy ? activeButtonClass : buttonClass}
                                onClick={this.handleClick}>
                            {__('Link With Discourse', 'wp-discourse')}
                        </button>
                    </label>
                </div>
            )
        } else {
            return '';
        }
    }
}

class PublishingResponse extends Component {
    constructor(props) {
        super(props);
    }

    render() {
        // Todo: wpdc_publishing_response is currently being cleared after a successful request, so it can't be used as a condition.
        // Todo: if the publishing response is present, use it to create the message.
        const response = this.props.wpdc_publishing_response;
        const permalink = this.props.discourse_permalink;
        let message;

        if (this.props.published) {
            if (permalink) {
                // Todo: turn this into a link.
                message = <p>Your post has been published to {permalink}.</p>
            } else {
                message = <p>There was an error publishing your post to Discourse.</p>
            }

            return (
                <div className={'wpdc-publishing-response'}>
                    {message}
                </div>
            );
        } else {
            return '';
        }
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
        this.setState({showPanel: !this.state.showPanel});
    }

    render() {
        if (this.props.published) {
            return (
                <div className={"wpdc-component-panel-body"}>
                    <h2 className={"wpdc-panel-section-title"}>
                        <button type="button" aria-expanded="false"
                                className={"components-button components-panel__body-toggle"}
                                onClick={this.togglePanel}>
                            <span aria-hidden="true">
                                {this.state.showPanel ? upArrow : downArrow}
                            </span>
                            {__('Unlink From Discourse', 'wp-discourse')}
                        </button>
                    </h2>
                    <div className={!this.state.showPanel ? 'hidden' : ''}>
                        <p className={'wpdc-info'}>
                            {__('Unlinking the post from Discourse will remove all Discourse metadata from the post.', 'wp-discourse')}
                        </p>
                        <button className={this.props.busy ? activeButtonClass : buttonClass}
                                onClick={this.handleClick}>{__('Unlink Post', 'wp-discourse')}
                        </button>
                    </div>
                </div>
            );
        } else {
            return '';
        }
    }
}

class UpdateDiscourseTopic extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showPanel: false,
        };
        this.handleClick = this.handleClick.bind(this);
        this.togglePanel = this.togglePanel.bind(this);
    }

    togglePanel() {
        this.setState({showPanel: !this.state.showPanel});
    }

    handleClick(e) {
        this.props.handleUpdateChange(e);
    }

    render() {
        if (this.props.published) {
            return (
                <div className={"wpdc-component-panel-body"}>
                    <h2 className={"wpdc-panel-section-title"}>
                        <button type="button" aria-expanded="false"
                                className={"components-button components-panel__body-toggle"}
                                onClick={this.togglePanel}>
                            <span aria-hidden="true">
                                {this.state.showPanel ? upArrow : downArrow}
                            </span>
                            {__('Update Discourse Topic', 'wp-discourse')}
                        </button>
                    </h2>
                    <div className={!this.state.showPanel ? 'hidden' : ''}>
                        <p className={'wpdc-info'}>
                            {__('Update the Discourse topic to the lastest saved version of the post.', 'wp-discourse')}
                        </p>
                        <button className={this.props.busy ? activeButtonClass : buttonClass}
                                onClick={this.handleClick}>
                            {__('Update Topic', 'wp-discourse')}
                        </button>
                        <p className={'wpdc-info-success'}>
                            {this.props.updateSuccessMessage}
                        </p>
                    </div>
                </div>
            );
        } else {
            return '';
        }
    }
}


class DiscourseSidebar extends Component {

    constructor(props) {
        super(props);
        // Todo: normalize state naming method; remove unused states
        this.state = {
            published: false,
            publishingMethod: 'publish_post',
            publish_to_discourse: 0,
            publish_post_category: pluginOptions.defaultCategory,
            discourse_post_id: null,
            discourse_topic_id: null,
            discourse_permalink: null,
            wpdc_publishing_response: null,
            wpdc_publishing_error: null,
            linked_topic_url: null,
            busyUnlinking: false,
            busyUpdating: false,
            busyLinking: false,
            updateSuccessMessage: '',
        };

        this.updateStateFromDatabase(this.props.postId);

        this.handlePublishChange = this.handlePublishChange.bind(this);
        this.handleCategoryChange = this.handleCategoryChange.bind(this);
        this.handleUnlinkFromDiscourseChange = this.handleUnlinkFromDiscourseChange.bind(this);
        this.handlePublishMethodChange = this.handlePublishMethodChange.bind(this);
        this.handleUpdateChange = this.handleUpdateChange.bind(this);
        this.handleLinkTopicClick = this.handleLinkTopicClick.bind(this);
    }

    updateStateFromDatabase(postId) {
        wp.apiFetch({path: `/wp/v2/posts/${postId}`, method: 'GET'}).then(
            (data) => {
                const meta = data.meta;
                // Todo: remove unused states; normalize state names
                this.setState({
                    published: meta.discourse_post_id > 0,
                    publish_to_discourse: meta.publish_to_discourse,
                    publish_post_category: meta.publish_post_category > 0 ? meta.publish_post_category : pluginOptions.defaultCategory,
                    discourse_post_id: meta.discourse_post_id,
                    discourse_topic_id: meta.discourse_topic_id,
                    discourse_permalink: meta.discourse_permalink,
                    wpdc_publishing_response: meta.wpdc_publishing_response,
                    wpdc_publishing_error: meta.wpdc_publishing_error,
                });
                return null;
            },
            (err) => {
                return err;
            }
        );
    }

    handlePublishMethodChange(publishingMethod) {
        this.setState({publishingMethod: publishingMethod});
    }

    handlePublishChange(publishToDiscourse) {
        this.setState({publish_to_discourse: publishToDiscourse ? 1 : 0}, () => {
            wp.apiRequest({
                path: '/wp-discourse/v1/set-publishing-options',
                method: 'POST',
                data: {
                    id: this.props.postId,
                    publish_to_discourse: this.state.publish_to_discourse,
                    publish_post_category: this.state.publish_post_category
                }
            }).then(
                (data) => {
                    return null;
                },
                (err) => {
                    return null;
                }
            );
        });
    }

    handleCategoryChange(category_id) {
        this.setState({publish_post_category: category_id}, () => {
            this.handlePublishChange(this.state.publish_to_discourse);
        });
    }

    handleLinkTopicClick(topicUrl) {
        this.setState({busyLinking: true});
        wp.apiRequest({
            path: '/wp-discourse/v1/link-topic',
            method: 'POST',
            data: {id: this.props.postId, topic_url: topicUrl}
        }).then(
            (data) => {
                // Todo: look at the data to make sure it's worked.
                this.setState({
                    busyLinking: false,
                    published: true,
                    discourse_permalink: data.discourse_permalink,
                });
                return null;
            },
            (err) => {
                return null;
            }
        );
    }

    handleUnlinkFromDiscourseChange(unlink_state) {
        this.setState({busyUnlinking: true});
        wp.apiRequest({
            path: '/wp-discourse/v1/unlink-post',
            method: 'POST',
            data: {id: this.props.postId}
        }).then(
            (data) => {
                this.setState({
                    busyUnlinking: false,
                    published: false,
                });
                return null;
            },
            (err) => {
                return null;
            }
        );
    }

    handleUpdateChange(e) {
        this.setState({
            busyUpdating: true,
            updateSuccessMessage: '',
        });
        wp.apiRequest({
            path: '/wp-discourse/v1/update-topic',
            method: 'POST',
            data: {id: this.props.postId}
        }).then(
            (data) => {
                // Todo: don't set the success message here. Check the metatdata and add message to publishing notificion area.
                this.setState({
                    busyUpdating: false,
                    updateSuccessMessage: __('The Discourse topic has been updated!', 'wp-discourse'),
                });
                return null;
            },
            (err) => {
                this.setState({busyUpdating: false});
                return null;
            }
        );
    }

    componentDidUpdate(prevProps) {
        const meta = this.props.post.meta,
            prevMeta = prevProps.post.meta;
        if (meta.discourse_post_id !== prevMeta.discourse_post_id ||
            meta.wpdc_publishing_response !== prevMeta.wpdc_publishing_response ||
            meta.wpdc_publishing_error !== prevMeta.wpdc_publishing_error) {
            this.setState({
                discourse_post_id: meta.post_id,
                discourse_permalink: meta.discourse_permalink,
                wpdc_publishing_response: meta.wpdc_publishing_response,
                wpdc_publishing_error: meta.wpdc_publishing_error,
            });
        }
    }

    render() {
        // Todo: it looks like there's a weird div in here
        return (
            <Fragment>
                <PluginSidebarMoreMenuItem
                    target="discourse-sidebar"
                >
                    {__('Discourse', 'wp-discourse')}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="discourse-sidebar"
                    title={__('Discourse', 'wp-discourse')}
                >
                    <PanelBody>
                        <div className={'wpdc-sidebar'}>
                            <PublishingOptions published={this.state.published}
                                               handlePublishMethodChange={this.handlePublishMethodChange}
                                               publishingMethod={this.state.publishingMethod}/>
                            <PublishToDiscourseCheckBox publishingMethod={this.state.publishingMethod}
                                                        published={this.state.published}
                                                        publishToDiscourse={this.state.publish_to_discourse}
                                                        handlePublishChange={this.handlePublishChange}/>
                            <DiscourseCategorySelect
                                publishingMethod={this.state.publishingMethod}
                                published={this.state.published}
                                category_id={this.state.publish_post_category || pluginOptions.defaultCategory}
                                handleCategoryChange={this.handleCategoryChange}
                            />
                            <div class="wpdc-link-to-topic">
                                <LinkToDiscourseTopic publishingMethod={this.state.publishingMethod}
                                                      published={this.state.published}
                                                      postId={this.props.postId}
                                                      busy={this.state.busyLinking}
                                                      handleLinkTopicClick={this.handleLinkTopicClick}
                                />
                            </div>
                        </div>
                        <PublishingResponse
                            published={this.state.published}
                            discourse_post_id={this.state.discourse_post_id}
                            wpdc_publishing_response={this.state.wpdc_publishing_response}
                            discourse_permalink={this.state.discourse_permalink}
                        />
                        <UnlinkFromDiscourse
                            published={this.state.published}
                            // postId={this.props.postId}
                            handleUnlinkFromDiscourseChange={this.handleUnlinkFromDiscourseChange}
                            busy={this.state.busyUnlinking}
                        />
                        <UpdateDiscourseTopic
                            published={this.state.published}
                            // postId={this.props.postId}
                            busy={this.state.busyUpdating}
                            handleUpdateChange={this.handleUpdateChange}
                            updateSuccessMessage={this.state.updateSuccessMessage}
                        />
                    </PanelBody>
                </PluginSidebar>
            </Fragment>
        )
    }
}

const HOC = withSelect((select, {forceIsSaving}) => {
    const {
        getCurrentPostId,
        getCurrentPost,
    } = select('core/editor');
    return {
        postId: getCurrentPostId(),
        post: getCurrentPost(),
    };
})(DiscourseSidebar);

registerPlugin('discourse-sidebar', {
    icon: iconEl,
    render: HOC,
});