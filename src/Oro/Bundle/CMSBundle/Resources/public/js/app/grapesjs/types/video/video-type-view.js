const undoAutoPlay = url => url.replace(/(autoplay=).*?(&)/, '$1' + 0 + '$2');

export default BaseTypeView => {
    const VideoTypeView = BaseTypeView.extend({
        constructor: function VideoTypeView(...args) {
            return VideoTypeView.__super__.constructor.apply(this, args);
        },

        onRender() {
            if (!this.model.get('initial')) {
                this.em.removeSelected();
                this.em.addSelected(this.el);
            }
            return this;
        },

        updateVideo() {
            VideoTypeView.__super__.updateVideo.call(this);
            const prov = this.model.get('provider');

            if (prov === 'so') {
                this.videoEl.autoplay = false;
            }
        },

        updateSrc() {
            VideoTypeView.__super__.updateSrc.call(this);
            const {videoEl} = this;
            if (!videoEl) {
                return;
            }
            const prov = this.model.get('provider');

            // Disable autoplay for source
            if (prov !== 'so') {
                videoEl.src = undoAutoPlay(videoEl.src);
            } else {
                videoEl.src = this.model.getSourceVideoSrc();
            }
        },

        renderByProvider(prov) {
            const videoEl = VideoTypeView.__super__.renderByProvider.call(this, prov);

            if (prov !== 'so') {
                videoEl.src = undoAutoPlay(videoEl.src);
            }

            this.videoEl = videoEl;
            this.updateVideo();

            return videoEl;
        }
    });

    return VideoTypeView;
};
