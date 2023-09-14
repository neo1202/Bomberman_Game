declare namespace GE {
    // 使用上弱型別any 就好，感覺還不需要多 GE.CustomEvent 的使用
    // export class CustomEvent extends Event {
    //     detail: any;
    //     propagationStopped: boolean;
    // }
    /**
     * @description 用來加Global的EventListener target假如是Component 當Destroy會砍掉 要自行拿掉的話請使用GS.removeCustomEvent
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    function addListener(name: string, callback: Function, target: any): Function;

    /**
     * @description 只執行一次的監聽，要自行拿掉的話請使用GS.removeCustomEvent
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    function addListenerOnce(name: string, callback: Function, target: any): Function;

    /**
     * @description 用來傳遞node之間的通知, 請使用this.node.on or off
     * @param {Node} node
     * @param {名字_string} name
     * @param {要傳的物件_obj} value
     */
    function dispatchEvent(node: Node, name: string, value: any): void;

    /**
     * @description 等同舊的cc.eventManager.dispatchCustomEvent
     * @param {名字_string} name
     * @param {要傳的物件_obj} value
     */
    function dispatchCustomEvent(name: string, value?: any): void;

    /**
     * @description 砍掉CustomEvent
     * @param {名字_string} name
     * @param {func} callback
     * @param {target} target
     */
    function removeCustomEvent(name: string, callback: Function, target: any): void;
}
