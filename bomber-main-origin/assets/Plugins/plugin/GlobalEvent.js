if (typeof (GE) === 'undefined') {
    GE = {};
}
GE._customEvent = new cc.EventTarget();

(function () {
    var CustomEvent = function (name, bubbles, value) {
        cc.Event.call(this, name, bubbles);
        this.detail = value;
    };

    /**
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    GE.addListener = function (name, callback, target) {
        if (!target) {
            cc.error('missing target');
        }

        if (target) {
            var node = target.node;
            if (node) {
                var destroyComponent = node.getComponent('GEDestroy');
                if (!destroyComponent) 
                    destroyComponent = node.addComponent('GEDestroy');

                // 加入Node被Destroy後的callback
                destroyComponent.addEventParam({
                    name: name,
                    func: callback,
                    target: target
                });
            }
        }
        
        // 對EventTarget註冊
        return GE._customEvent.on(name, callback, target);
    };

    /**
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    GE.addListenerOnce = function (name, callback, target) {
        var newCallbackFunc = function (event) {
            GE.removeCustomEvent(name, newCallbackFunc, target);
            callback && callback(event);
        };
        return GE.addListener(name, newCallbackFunc, target);
    };

    /**
     * @param {Node} node
     * @param {名字_string} name
     * @param {要傳的物件_obj} value
     */
    GE.dispatchEvent = function (node, name, value) {
        if (node) {
            var event = new CustomEvent(name, true, value);
            node.dispatchEvent(event);
        }
    };

    /**
     * @param {名字_string} name
     * @param {要傳的物件_obj} value
     */
    GE.dispatchCustomEvent = function (name, value) {
        GE._customEvent.emit(name, value);
    };

    /**
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    GE.removeCustomEvent = function (name, callback, target) {
        GE._customEvent.off(name, callback, target);
    };

})();
