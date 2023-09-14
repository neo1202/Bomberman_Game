import { _decorator, assetManager, Canvas, CCInteger, Component, director, find, Input, instantiate, log, Node, NodePool, Pool, Prefab, resources } from 'cc';
const { ccclass, property } = _decorator;

export enum Object {
    Player,
    Bomb
}

@ccclass('PoolManager')
/**物件池 */
export class PoolManager extends Component {

    private static _instance: PoolManager;
    public static get instance() {
        if (this._instance) {
            return this._instance;
        }
        // let PMNode = find(PoolManager.name);
        // this._instance = PMNode.getComponent(PoolManager.name) as PoolManager;
        // this._instance._init();
        return this._instance;
    }

    static NodeName = "";
    @property([Prefab])
    public prefabs: Prefab[] = [];
    @property([CCInteger])
    public defaultAmounts: number[] = [];

    private _nameToIndex: Map<string, number> = new Map();

    private _nodePool: NodePool[] = [];

    update(deltaTime: number) {
    }

    private _init() {
        director.addPersistRootNode(this.node);

        //隨著prefabs數量創建池
        for (let i = 0; i < this.prefabs.length; i++) {
            this._nodePool[i] = new NodePool();
            //根據prefab創造預設數量並放入池中
            for (let j = 0; j < this.defaultAmounts[i]; j++) {
                this._nodePool[i].put(instantiate(this.prefabs[i]));
            }
            this._nameToIndex.set(this.prefabs[i].name, i);
        }
    }

    /**
     * 從物件池取得物件，如果物件池的東西不夠用會再生
     * @param nodeName 
     * @returns 
     */
    //HACK: 可讀性低，而且這個參數=name，那乾脆用name就好
    // reuse(prefabClass: { new(...args: any[]): Component; }) {
    reuse(nodeName: string ) {

        let instance = PoolManager.instance;
        //prefab與script名稱一致的
        //HACK: 特意繞一圈來拿name，不如用enum存name

        // let nodeName = new prefabClass().name;
        if (!instance._nameToIndex.has(nodeName))
            throw "沒這東西,請檢查Class或Insepctor是否有正確引用,或是prefab與script是否一致";
        let index = instance._nameToIndex.get(nodeName);
        let remainingAmount = instance._nodePool[index].size();
        //物件池中的物件不夠了
        if (remainingAmount == 0) {
            log("不夠了，再生一個")
            instance._nodePool[index].put(instantiate(instance.prefabs[index]));
        }
        let node = instance._nodePool[index].get();
        node.parent = this.node.parent;

        return node;
    }

    unuse(node: Node) {
        let instance = PoolManager.instance;
        let index = instance._nameToIndex.get(node.name);
        instance._nodePool[index].put(node);
    }

    protected onLoad(): void {
        PoolManager._instance = this;
        this._init();
    }

    protected onDestroy(): void {
        PoolManager._instance = null;
    }
}

