<?php

/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 2010-2012 Logic Works GmbH
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *  
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 * ************************************************************************* */

class lw_inherit extends lw_advanced_plugin  
{
	function __construct()
	{
		parent::__construct();
		$this->repository = lw_registry::getInstance()->getEntry("repository");
	}
	
	function buildPageOutput()
	{
		if ($this->params['type'] == "item") {
			$path = lw_page::getInstance()->getPageValue('path').lw_page::getInstance()->getPageValue('id').':';	
		} else {
			$path = lw_page::getInstance()->getPageValue('path');
		}
        $parts = explode(":", $path);
       
        $add = '';
        foreach($parts as $part) {
        	if (!empty($part)) $add.=' page_id = '.intval($part).' OR';
        }
        $add = substr($add,0,-2);
      
       	$add = " ( ".$add." ) ";
		if ($this->params['type'] == "item") {
			$this->db->setStatement("SELECT t:lw_items.* FROM t:lw_items, t:lw_itemtypes WHERE t:lw_items.itemtype = t:lw_itemtypes.classname AND t:lw_itemtypes.name like '%(inherit)%' AND ".$add);
	        $itemsInPath = $this->db->pselect();

	        if (count($itemsInPath) == 0) return;
	        
	        $currentPage = lw_registry::getInstance()->getEntry('pid');
	        
	        foreach($itemsInPath as $index=>$item) {
	        	$opt1clob = $item['opt1clob'];
	        	if (!empty($opt1clob)) {
	        		if (strpos($opt1clob,':'.$currentPage.':') !== false) {
	        			unset($itemsInPath[$index]);
	        		}
	        	}
	        }
	        
			if ($this->params['tid']>0) {
			    $template = $this->getTemplate($this->params['tid']);
			    if (strlen(trim($template))>0) {
			        $ttpl = new lw_te($template);
			        $subtemplate = $ttpl->getBlock("item");
			        foreach($itemsInPath as $item) {
                        $tpl = new lw_te($subtemplate);
                        $tpl->reg("description", $item['description']);
                        $tpl->reg("info",       $item['info']);
                        if ($item['filename']) {
                            $tpl->setIfVar('image');
                            $tpl->reg("image",      $this->config['url']['datapool'].'_items/item_'.$item['id'].'/'.$item['filename'].'.'.$item['filetype']);
                            $tpl->reg("alternativ", $item['opt1text']);
                        }
                        if ($item['url']) {
                            $tpl->setIfVar('url');
                            $tpl->reg("url",        $item['url']);
                            if ($item['opt2text']) {
                                $tpl->setIfVar('linktext');
                                $tpl->reg("linktext",   $item['opt2text']);
                            }
                        }
                        $tout.=$tpl->parse();
			        }
			        $ttpl->putBlock("item", $tout);
			        return $ttpl->parse();
			    }
			}
		    $view = new lw_view($this->config['plugin_path']['lw']."lw_inherit/templates/show.tpl.phtml");
		    $view->items = $itemsInPath;
		    return $view->render();
			
		} elseif($this->params['type'] == "cbox") {
			$this->db->setStatement("SELECT DISTINCT t:lw_container.* FROM t:lw_container, t:lw_cobject WHERE t:lw_container.object_id = t:lw_cobject.id AND t:lw_cobject.description like '%(inherit)%' AND ".$add);
	        $erg = $this->db->pselect();

			foreach($erg as $entry) {	        	
				$parser = new lw_fe_parser();
				
				$set 	= $this->repository->getRepository('cobject')->getCObjectById($entry['object_id']);
				$data 	= $this->repository->loadEAV($entry['id']);
				
				foreach($data as $key => $value) {
				    $key = str_replace('lw_', '', $key);
				    $set['data'][$key] = $value;
				}
				
				$parser->setID($entry['id']);
				$parser->setOID($entry['object_id']);
				$parser->setTemplate($set['template']);
				$parser->setData($set['data']);
				$out.= $parser->parse();	        
			}
			return $out;
		}
		return false;
	}
}
