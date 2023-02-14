<?php
namespace booosta\tablelister;
\booosta\Framework::init_module('tablelister');

class Tablelister extends \booosta\base\Module
{
  use moduletrait_tablelister;

  protected $data;
  protected $tabletags, $use_datatable;
  protected $class, $table_class, $th_class, $tr_class, $tr_class_odd, $tr_class_even, $td_class, $thead_class, $tbody_class;
  protected $th_field_class;
  protected $td_field_class;
  protected $id;

  protected $table_attributes;
  protected $th_attributes;
  protected $row_attributes;
  protected $data_attributes;
  protected $col_data_attributes;

  protected $keyfilter;
  protected $fkeyfilter;
  protected $header;
  protected $always_show_header;
  protected $fkeys;
  protected $showfields, $hidefields;
  protected $links, $links_condition;
  protected $extrafields;
  protected $condition;
  protected $db_fk;
  protected $array_fk;
  protected $nvl;
  protected $replaces;
  protected $omit_columns = [];

  protected $datatable_libpath, $datatable_display_length;
  protected $datatable_ajaxurl;
  protected $autoheader;
  protected $lightmode;

  public function __construct($data, $tabletags = true, $use_datatable = false)
  {
    #\booosta\Framework::debug($data);
    parent::__construct();

    $this->id = '0';
    $this->data = $data;
    $this->tabletags = $tabletags;
    $this->use_datatable = $use_datatable;

    $this->table_attributes = [];
    $this->row_attributes = [];
    $this->th_attributes = [];
    $this->data_attributes = [];
    $this->col_data_attributes = [];
    $this->nvl = [];

    $this->keyfilter = 'true';
    $this->fkeyfilter = 'true';

    $this->db_fk = null;
    $this->array_fk = null;

    $this->header = null;
    $this->always_show_header = false;
    $this->fkeys = [];
    $this->links = [];
    $this->links_condition = [];
    $this->extrafields = [];
    $this->condition = [];
    $this->replaces = [];
   
    $this->datatable_libpath = 'lib/modules/datatable';
  }

  public function set_id($id) { $this->id = $id; }
  public function set_keyfilter($filter) { $this->keyfilter = str_replace('%', '$key', $filter); }
  public function set_fkeyfilter($filter) { $this->fkeyfilter = str_replace('%', '$fkey', $filter); }
  public function set_header($header) { $this->header = $header; }
  public function set_links($links) { $this->links = $links; }
  public function add_link($field, $link) { $this->links[$field] = $link; }
  public function set_extrafields($extrafields) { $this->extrafields = $extrafields; }
  public function add_extrafield($extrafield, $key = null) { if($key) $this->extrafields[$key] = $extrafield; else $this->extrafields[] = $extrafield; }

  public function set_nvl($field, $value) { $this->nvl[$field] = $value; }
  public function set_conditions($conditions) { $this->condition = $conditions; }
  public function add_condition($key, $condition) { $this->condition[$key] = $condition; }
  public function use_datatable($flag) { $this->use_datatable = $flag; }
  public function always_show_header($flag) { $this->always_show_header = $flag; }
  public function set_replaces($replaces) { $this->replaces = $replaces; }

  public function set_class($class) { $this->class = $class; }
  public function set_table_class($class) { $this->table_class = $class; }
  public function set_th_class($class) { $this->th_class = $class; }
  public function set_tr_class($class) { $this->tr_class = $class; }
  public function set_tr_class_odd($class) { $this->tr_class_odd = $class; }
  public function set_tr_class_even($class) { $this->tr_class_even = $class; }
  public function set_td_class($class) { $this->td_class = $class; }

  public function set_table_attribute($var, $val) { $this->table_attributes[$var] = $val; }
  public function set_th_attribute($var, $val) { $this->th_attributes[$var] = $val; }
  public function set_row_attribute($var, $val) { $this->row_attributes[$var] = $val; }
  public function set_data_attribute($var, $val) { $this->data_attributes[$var] = $val; }
  public function set_col_data_attribute($col, $var, $val) { $this->col_data_attributes[$col][$var] = $val; }
  
  public function set_omit_columns($data) { $this->omit_columns = $data; }
  public function add_omit_column($data) { $this->omit_columns[] = $data; }

  public function set_datatable_libpath($path) { $this->datatable_libpath = $path; }
  public function set_datatable_display_length($display_length) { $this->datatable_display_length = $display_length; }
  public function set_datatable_ajaxurl($ajaxurl) { $this->datatable_ajaxurl = $ajaxurl; }
  public function set_autoheader($autoheader) { $this->autoheader = $autoheader; }
  public function set_lightmode($lightmode) { $this->lightmode = $lightmode; }

  public function add_replaces($key, $replaces) 
  { 
    if(is_array($key)):
       $this->replaces = array_merge($this->replaces, $key);
    else:
      if(is_array($replaces)) $replaces = array_shift($replaces);   // functions can only be passed in an array
      $this->replaces[$key] = $replaces;
    endif;
  }

  public function add_link_condition($field, $link, $condition)
  {
    $this->links[$field] = $link;
    $this->links_condition[$field] = $condition;
  }

  public function set_th_field_class($field, $class = null)
  {
    if($class === null) $this->th_field_class = $field;
    else $this->th_field_class[$field] = $class;
  }

  public function set_td_field_class($field, $class = null) 
  { 
    if($class === null) $this->td_field_class = $field;
    else $this->td_field_class[$field] = $class;
  }

  public function show_fields($keys)
  {
    if(!is_array($keys)) $keys = explode(',', $keys);

    if(is_array($this->hidefields)) $keys = array_diff($keys, $this->hidefields);
    $this->showfields = $keys;

    $keystr = [];
    foreach($keys as $key) $keystr[] = " % == '$key' ";
    $keystring = implode('||', $keystr);

    #\booosta\debug("show_fields: $keystring");
    $this->set_fkeyfilter($keystring);
  }

  public function hide_fields($keys)
  {
    if(!is_array($keys)) $keys = explode(',', $keys);
    $this->hidefields = $keys;

    if(is_array($this->showfields)):
      if(!isset($this->orig_showfields)) $this->orig_showfields = $this->showfields;
      $showfields = array_diff($this->orig_showfields, $keys);
      $this->show_fields($showfields);
    else:
      $keystr = [];
      foreach($keys as $key) $keystr[] = " % != '$key' ";
      $keystring = implode('&&', $keystr);
      #\booosta\debug("hide_fields: $keystring");
      $this->set_fkeyfilter($keystring);
    endif;
  }

  public function set_foreignkey_db($field, $table = null, $idfield = 'id', $showfield = 'name')
  {
    if($table === null) $table = $field;
    $this->db_fk[$field] = ['table'=>$table, 'idfield'=>$idfield, 'showfield'=>$showfield];
  }

  public function set_foreignkey_array($field, $data)
  {
    if(is_array($data)) $this->array_fk[$field] = $data;
    else return false;

    return true;
  }

  public function get_html()
  {
    global $key, $fkey, $SERIAL_FIELD;
    #\booosta\debug($this->fkeyfilter);
    #\booosta\debug($this->links);

    if(!is_array($this->data)) return '';

    if($this->class) $classtag = "class='$this->class'"; else $classtag = '';
    if($this->table_class) $table_classtag = "class='$this->table_class'"; else $table_classtag = '';
    if($this->th_class) $th_classtag = "class='$this->th_class'"; else $th_classtag = '';
    if($this->tr_class) $tr_classtag = "class='$this->tr_class'"; else $tr_classtag = '';
    if($this->tr_class_odd) $tr_classtag_odd = "class='$this->tr_class_odd'"; else $tr_classtag_odd = '';
    if($this->tr_class_even) $tr_classtag_even = "class='$this->tr_class_even'"; else $tr_classtag_even = '';
    if($this->td_class) $td_classtag = "class='$this->td_class'"; else $td_classtag = '';
    $td_classtag_default = $td_classtag;
    $th_classtag_default = $th_classtag;

    $data = $this->data;
    #\booosta\Framework::debug($data);

    // header
    if($this->use_datatable || $this->always_show_header):
      $autoheader_exclude = $this->config('autoheader_exclude') ?? [];
      if(is_string($autoheader_exclude)) $autoheader_exclude = explode(',', $autoheader_exclude);

      #\booosta\debug($this->showfields);
      #\booosta\Framework::debug($this->fkeyfilter);
      if($this->header): $header = $this->header;
      elseif(is_array($this->showfields)): 
        $sfields = array_diff($this->showfields, $autoheader_exclude);
        $header = implode(',', $sfields);
      elseif($this->showfields): $header = $this->showfields;
      elseif(is_array($data[0])):  // show fields and extrafields
        $header = [];
        foreach($data[0] as $fkey=>$dummy):
          if(!\booosta\Framework::ifeval($this->fkeyfilter)) continue;
          $header[] = in_array($fkey, $autoheader_exclude) ? '' : ucfirst($fkey);
        endforeach;

        foreach($this->extrafields as $fkey=>$dummy):
          if(!\booosta\Framework::ifeval($this->fkeyfilter)) continue;
          $header[] = in_array($fkey, $autoheader_exclude) ? '' : ucfirst($fkey);
        endforeach;
      endif;
    endif;
    #\booosta\debug($header);

    // reorder data elements if show_fields is given
    #\booosta\debug("data:"); \booosta\debug($data);
    if($this->showfields):
      $data1 = [];
      foreach($data as $rowdata):
        $data2 = [];
        foreach($this->showfields as $showfield):
          if(array_key_exists($showfield, $rowdata)) $data2[$showfield] = $rowdata[$showfield];
          #\booosta\debug("rowdata:"); \booosta\debug($rowdata);
          unset($rowdata[$showfield]);
        endforeach;

        // push remaining elements not in show_fields
        $data2 = array_merge($data2, $rowdata);

        $data1[] = $data2;
      endforeach;
      $data = $data1;
    endif;

    $ret = '';
    if($this->tabletags):
      $extra = '';
      foreach($this->table_attributes as $att=>$val) $extra .= "$att='$val' ";
      if($this->use_datatable) $tag = 'datatable_'; else $tag = 'tablelister_';
      $ret .= "<div class='table-responsive'><table id='$tag$this->id' width='100%' $table_classtag $classtag $extra>";
    endif;

    if(is_string($header) && $header != '') $header = explode(',', $header);
    #\booosta\Framework::debug("ret: $ret");

    if(is_array($header)):
      // reorder header elements for same order as data
      $newheader = [];

      reset($header);
      reset($data);
      if(!is_numeric(key($header)) && sizeof($data) > 0 && is_array(current($data))):
        foreach(current($data) as $dkey=>$dat)
          $newheader[$dkey] = $header[$dkey];
      else:
        $newheader = $header;
      endif;

      $ret .= '<thead><tr>';
      $extrath = '';
      #\booosta\debug($newheader);
      foreach($newheader as $fkey=>$fheader):
        #\booosta\Framework::debug($fkey);
        #\booosta\Framework::debug($this->fkeyfilter);
        if(!is_numeric($fkey) && !\booosta\Framework::ifeval($this->fkeyfilter)) continue;
        foreach($this->th_attributes as $att=>$val) $extrath .= "$att='$val' ";

        #\booosta\debug($this->th_field_class);
        if(is_array($this->th_field_class) && isset($this->th_field_class[$fkey])) $th_classtag = "class='{$this->th_field_class[$fkey]}'";
        else $th_classtag = $th_classtag_default;

        if(in_array($fheader, $this->omit_columns)) $omithead = ' class="min-desktop" '; else $omithead= ' class="all" ';
        $ret .= "<th $th_classtag $classtag $extrath $omithead>$fheader</th>";
      endforeach;
      #\booosta\debug($ret);

      $missing_headers = sizeof($this->showfields ?? []) - sizeof($header);

      for($i = 0; $i < $missing_headers; $i++)
        $ret .= "<th width='20px' data-orderable='false' $th_classtag $classtag $extrath class='all'>&nbsp;</th>";

      $ret .= "</tr></thead>\n";
    elseif($this->use_datatable):
      $maxsize = 0;
      $fheader = '';

      foreach($data as $dat) $maxsize = max($maxsize, sizeof($dat));
      for($i=0; $i<$maxsize; $i++) $fheader .= "<th>$i</th>";
      $ret .= "<thead><tr>$fheader</tr></thead>";
    endif;

    $rowbit = 0;
    $rowcount = 0;

    $tbody_class = $this->tbody_class ? "class='$this->tbody_class'" : '';
    $ret .= "<tbody $tbody_class>";
    #\booosta\debug($ret);

    foreach($data as $key=>$row):
      #\booosta\ttrace("new row $key");
      if(!is_array($row)) continue;
      if(!\booosta\Framework::ifeval($this->keyfilter)) continue;

      $rowcount++;
      $tr_classtag_oe = $rowcount % 2 ? $tr_classtag_odd : $tr_classtag_even;

      $extra = '';
      foreach($this->row_attributes as $att=>$val):
        if($att == "class") $val .= "$rowbit";
        $extra .= "$att='$val' ";
      endforeach;
      $ret .= "<tr $tr_classtag $tr_classtag_oe $classtag $extra>";

      foreach($row as $fkey=>$dat):
        #\booosta\ttrace("fkey $fkey");
        if(is_numeric($fkey)) $fkey = "$fkey";  // convert to string

        if($fkey == 'ser__obj') continue;
        if(is_array($SERIAL_FIELD) && in_array($fkey, $SERIAL_FIELD)) continue;
        if(!\booosta\Framework::ifeval($this->fkeyfilter)) continue;
        if(array_key_exists($fkey, $this->extrafields)) continue;   // show extrafields in next loop
        #\booosta\ttrace(2);

        if(is_array($this->td_field_class) && isset($this->td_field_class[$fkey])) $td_classtag = "class='{$this->td_field_class[$fkey]}'";
        else $td_classtag = $td_classtag_default;

        if($this->condition[$fkey]):
          if(strstr($this->condition[$fkey], '{id}')) $condition = str_replace('{id}', $row['id'], $this->condition[$fkey]);
          elseif(strstr($this->condition[$fkey], '{')) 
            $condition = preg_replace_callback('/{([^}]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $this->condition[$fkey]); 
          //replace {var} with $row[var] but only if that exists

          if(!\booosta\Framework::ifeval($condition)):
            $ret .= "<td $td_classtag $classtag>&nbsp;</td>";
            continue;
          endif;
        endif;
        #\booosta\ttrace(3);

        if(isset($this->db_fk[$fkey])):
          $sql = "select `{$this->db_fk[$fkey]['showfield']}` from `{$this->db_fk[$fkey]['table']}` where `{$this->db_fk[$fkey]['idfield']}`='$dat'";
          $dat = $this->DB->query_value($sql);
        endif;
        #\booosta\ttrace(4);

      	if(isset($this->array_fk[$fkey])) $dat = $this->array_fk[$fkey][$dat];

        $extra1 = '';
        foreach($this->data_attributes as $att=>$val) $extra1 .= "$att='$val' ";

        if(is_array($this->col_data_attributes[$fkey]))
          foreach($this->col_data_attributes[$fkey] as $att=>$val) $extra1 .= "$att='$val' ";

        $link1 = $link2 = '';
        if($link = $this->links[$fkey]):
          if($this->links_condition[$fkey]):
            if(strstr($this->links_condition[$fkey], '{id}')) $condition = str_replace('{id}', $row['id'], $this->links_condition[$fkey]);
            elseif(strstr($this->links_condition[$fkey], '{'))
              $condition = preg_replace_callback('/{([^}]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $this->links_condition[$fkey]);
            else $condition = $this->links_condition[$fkey];

            $show_link = \booosta\Framework::ifeval($condition);
            #\booosta\debug("condition: $condition, result: $show_link");
          else:
            $show_link = true;
          endif;

          if(strstr($link, '{id}')) $link = str_replace('{id}', $row['id'], $link);
          elseif(strstr($link, '{fkid}')) $link = str_replace('{fkid}', $dat, $link);
          elseif(strstr($link, '{')) $link = preg_replace_callback('/{([^}]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $link);  
          // replace {var} with $row[var] but only if that exists

          if($show_link):
            $link1 = "{LINK|"; 
            $link2 = "|$link}";
          endif;
        endif;
        #\booosta\ttrace(5);

      	if($dat == '' && $this->nvl[$fkey]):
      	  $dat = $this->nvl[$fkey];
      	endif;

        #\booosta\Framework::debug($dat);
        if(strstr($dat, '{id}')) $dat = str_replace('{id}', $row['id'], $dat);
        elseif(strstr($dat, '{')) $dat = preg_replace_callback('/{([A-Za-z0-9_]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $dat);   // replace {var} with $row[var] but only if that exists
        #\booosta\Framework::debug($dat);

        if(isset($this->replaces[$fkey])):
          $repl = $this->replaces[$fkey];
          if(!is_string($repl) && is_callable($repl)):
            $func_reflection = new \ReflectionFunction($repl);
            $num_of_params = $func_reflection->getNumberOfParameters();

            if($num_of_params == 1) $dat = $repl($dat);
            else $dat = $repl($dat, $row['id']);
          else:
            $dat = $repl;
          endif;
        endif;
        
        if(strstr($dat, '{id}')) $dat = str_replace('{id}', $row['id'], $dat);
        elseif(strstr($dat, '{')) $dat = preg_replace_callback('/{([A-Za-z0-9_]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $dat);   // replace {var} with $row[var] but only if that exists

        $dat = stripslashes($dat);
        $ret .= "<td $td_classtag $classtag $extra1>$link1$dat$link2</td>";
        #\booosta\ttrace(6);
      endforeach;

      if(is_array($this->extrafields))
        foreach($this->extrafields as $fkey=>$ef):
          #\booosta\ttrace($fkey);
          #\booosta\debug($this->fkeyfilter);
          if(!\booosta\Framework::ifeval($this->fkeyfilter)) continue;

          if(is_array($this->td_field_class) && isset($this->td_field_class[$fkey])) $td_classtag = "class='{$this->td_field_class[$fkey]}'";
          else $td_classtag = $td_classtag_default;

          if($this->condition[$fkey]):
            if(strstr($this->condition[$fkey], '{id}')) $condition = str_replace('{id}', $row['id'], $this->condition[$fkey]);
            elseif(strstr($this->condition[$fkey], '{')) $condition = preg_replace_callback('/{([^}]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $this->condition[$fkey]); //replace {var} with $row[var] 
            else $condition = $this->condition[$fkey];

            if(!\booosta\Framework::ifeval($condition)):
              $ret .= "<td $td_classtag $classtag>&nbsp;</td>";
              continue;
            endif;
          endif;
          #\booosta\ttrace(2);

          $extra1 = '';
          foreach($this->data_attributes as $att=>$val) $extra1 .= "$att='$val' ";
          #\booosta\ttrace(3);
      	  if(is_array($this->col_data_attributes[$fkey]))
            foreach($this->col_data_attributes[$fkey] as $att=>$val) $extra1 .= "$att='$val' ";
          #\booosta\ttrace(4);
          $link1 = $link2 = '';
          if($link = $this->links[$fkey]):
            if(strstr($link, '{id}')) $link = str_replace('{id}', $row['id'], $link);
            elseif(strstr($link, '{')) $link = preg_replace_callback('/{([^}]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $link);  // replace {var} with $row[var] but only if that exists

            $link1 = "{LINK|"; $link2 = "|$link}";
          endif;
          #\booosta\ttrace(5);

          if(strstr($ef, '{id}')) $efstr = str_replace('{id}', $row['id'], $ef);
          elseif(strstr($ef, '{')) $efstr = preg_replace_callback('/{([A-Za-z0-9_]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $ef);  // replace {var} with $row[var] but only if that exists
          else $efstr = $ef;

          if(isset($this->replaces[$fkey])): 
            $repl = $this->replaces[$fkey];
            if(!is_string($repl) && is_callable($repl)):
              $efstr = $repl($efstr);
            else:
              $efstr = $repl;
            endif;
          endif;

          if(strstr($efstr, '{id}')) $efstr = str_replace('{id}', $row['id'], $efstr);
          if(strstr($efstr, '{')) $efstr = preg_replace_callback('/{([A-Za-z0-9_]+)}/', function($m) use($row){ return isset($row[$m[1]])?$row[$m[1]]:$m[0]; }, $efstr);  // replace {var} with $row[var] but only if that exists
          
          $ret .= "<td $td_classtag $classtag $extra1>$link1$efstr$link2</td>";
        endforeach;

      $rowbit = ($rowbit == 0 ? 1 : 0);
      $ret .= "</tr>\n";
    endforeach;

    if($this->tabletags) $ret .= '</tbody></table></div>';
    #\booosta\Framework::debug($ret);

    if(\booosta\Framework::module_exists('datatable') && $this->use_datatable && $this->tabletags):
      $table = $this->makeInstance('Datatable', $this->id, $ret);
      if($this->datatable_display_length) $table->set_display_length($this->datatable_display_length);
      if(is_array($this->omit_columns)) $table->set_omit_columns($this->omit_columns);
      $table->set_autoheader($this->autoheader);

      if(isset($this->lightmode)) $table->set_lightmode($this->lightmode);
      if($this->use_datatable === 'ajax' && $this->datatable_ajaxurl) $table->set_ajaxurl($this->datatable_ajaxurl);
      
      if($this->tbody_class) $table->set_tbody_class($this->tbody_class);
      if($this->tr_class) $table->set_tr_class($this->tr_class);
      
      return $table->get_html();
    endif;

    return $ret;
  }

  public function get_html_includes()
  {
    if(\booosta\Framework::module_exists('datatable') && $this->use_datatable):
      $dummy = $this->makeInstance('Datatable');
      return $dummy->get_html_includes($this->datatable_libpath);
    endif;

    return '';
  }
} 
