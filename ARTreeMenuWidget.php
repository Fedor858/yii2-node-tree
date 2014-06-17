<?php

namespace bariew\nodeTree;

class ARTreeMenuWidget extends \yii\base\Widget
{
    public $items;
    public $behavior; // ARTreeBehavior instance
    public $view = 'node';
    public $selector = '#jstree';
    public $options = [];
    public $binds = [];
    
    
    protected static $commonOptions = [
        "core" => [
            "check_callback" => true,
            "animation" => 0
        ],
        "plugins" => [
            "contextmenu", "dnd", "search", "types"
        ],
        "dnd"   => [
            "is_draggable"  => true
        ],
        "types" => [
            "folder"        => ["icon" => "glyphicon glyphicon-file"]
        ],
        "search"  =>   [
            "fuzzy"   => false
        ],
        
        "contextmenu" => [
            "show_at_node"    => false,
            "items" =>    [
                "create"  =>  [
                    "label"   => "<i class='glyphicon glyphicon-plus' title='Create'></i>",
                    "action" => 'function(obj){
                        var url = replaceTreeUrl($(obj.reference[0]).attr("href"), "tree-create");
                        var id = $(obj.reference[0]).data("id");
                        var title = "New node";

                        $.post(url, {"attributes" : {"title" : title} }, function(data){
                            var attributes = JSON.parse(data);
                            var url = replaceTreeUrl(attributes["a_attr"]["href"], "tree-update");
                            window.location.href = attributes["a_attr"]["href"];
                        });
                    }'
                ],
                "rename"  => [
                    "label"  => "<i class='glyphicon glyphicon-font' title='Rename'></i>",
                    "action"  => 'function(obj){
                        var sel = jstree.jstree(true).get_selected();
                        arTreeRename(sel[0], false, jstree);
                    }'
                ],
                "edit"    => [
                    "label"   => "<i class='glyphicon glyphicon-pencil' title='Edit'></i>",
                    "action"  => 'function(obj){
                        var url = replaceTreeUrl( $(obj.reference[0]).attr("href"), "update");
                        arTreeShowUpdate(url);
                    }'
                ],
                "delete" => [
                    "label"   => "<i class='glyphicon glyphicon-trash' title='Delete'></i>",
                    "action" => 'function(obj) {
                        var url = replaceTreeUrl($(obj.reference[0]).attr("href"), "tree-delete");
                        if(confirm("Delete node?")) {
                            $.get(url, function(){
                                var ref = jstree.jstree(true),
                                sel = ref.get_selected();
                                if(!sel.length) { return false; }
                                ref.delete_node(sel);
                            });
                        }
                    }'
                ]
            ]
        ]
    ];
    
    protected static $commonBinds = [
        'move_node.jstree'  => 'function(event, data){
            $.ajax({
                type: "POST",
                url: replaceTreeUrl(data.node.a_attr.href, "tree-move"),
                data: {
                    pid     : data.parent.replace("node-", ""),
                    position: data.position
                },
                success: function(data){},
                error: function(xhr, status, error){
                    alert(status);
                }
            });
        }'
    ];
    
    public function run()
    {
        if(!$items = $this->items){
            return;
        }
        $this->registerScripts();
        return $this->render($this->view, [
            'items'     => $this->items, 
            'behavior'  => $this->behavior
        ]);
    }
    
    protected function registerScripts()
    {
        $view = $this->getView();
        ARTreeAssets::register($view);
        $options = $this->jsonEncode(array_merge(self::$commonOptions, $this->options));
        $binds = array_merge(self::$commonBinds, $this->binds);        
        $content = "var jstree = $('{$this->selector}'); jstree.jstree({$options});";
	foreach($binds as $event => $function){
            $content .= "jstree.bind('".$event."', $function);";
        }
        $view->registerJs($content);
    }
    
    public $jsonValues = [];
    public $jsonKeys = [];
    /**
     * 
     * @param type $content
     * @return type
     * @link http://solutoire.com/2008/06/12/sending-javascript-functions-over-json/
     */
    public function jsonEncode($content, $level = 0)
    {
        foreach($content as &$value){
            if (is_array($value)) {
                $value = $this->jsonEncode($value, 1);
                continue;
            }
            if(strpos($value, 'function(')===0){
                $this->jsonValues[] = $value;
                $value = '%' . md5($value) . '%';
                $this->jsonKeys[] = '"' . $value . '"';
            }
        }
        return ($level > 0)
            ? $content
            : str_replace($this->jsonKeys, $this->jsonValues, json_encode($content));
    }
}