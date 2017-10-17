<?php
/**
 * 个人学习自定义网站导航
 *
 * 20171017 - 第1版本
 *
 * Function
 * （功能）
 * - 自带常用技术网站
 * - 个人学习网站，快速浏览
 * - 可记录查看次数
 * - 可添加编辑网站
 * - 可自定义排序
 * - 可定义常用网站悬浮于左右页面
 * - 快速搜索功能
 *
 * Install
 * （安装）
 * 1. 创建库，默认库名nav
 * 2. 导入表结构，db/nav.sql
 * 3. 修改$DB_CONFIG数据库配置
 *
 * Usage
 * （使用方法）
 * 1. 下载源码，直接点击start.bat运行，默认打开chrome浏览器访问
 * 2. 下载源码，浏览器运行nav.php
 *
 * MIT license
 * Github: https://github.com/sunshinexcode/MySiteNav
 * Email: 24xinhui@163.com
 *
 * @author sunshine
 */

error_reporting(E_ERROR);
date_default_timezone_set('Asia/Shanghai');

// 配置
$DB_CONFIG = [
    'host' => 'localhost',
    'db' => 'nav',
    'user' => 'root',
    'pwd' => '',
];

// 连接数据库
$pdo = connect_pdo($DB_CONFIG);

// Ajax操作
switch ($_GET['act']) {
    case 'click_website': // 网站导航, 点击网站
        update_pdo($pdo, 'website', ['visit_count' => 'visit_count+1', 'updated_at' => date('Y-m-d H:i:s')], $_POST);
        break;
    case 'add_website': // 网站导航, 添加
        insert_pdo($pdo, 'website', array_merge(['created_at' => date('Y-m-d H:i:s')], $_POST));
        break;
    case 'edit_website': // 网站导航, 编辑
        update_pdo($pdo, 'website', array_merge(['updated_at' => date('Y-m-d H:i:s')], $_POST), ['id' => $_POST['id']]);
        break;
}

// 获取数据
$website_info = query_pdo($pdo, 'website', ['order' => 'visit_count DESC, category_name, sub_category_name']);
$website_left_info = query_pdo($pdo, 'website', ['where' => ['is_show_menu' => 1, 'menu_position' => 1], 'order' => 'display_order']);
$website_right_info = query_pdo($pdo, 'website', ['where' => ['is_show_menu' => 1, 'menu_position' => 2], 'order' => 'display_order']);

// PDO查询数据
function query_pdo($pdo, $table, $data)
{
    // 处理条件字段
    $fields_where[] = '1=1';
    foreach ($data['where'] as $field => $val) {
        $bind = ':' . $field;
        // 数组, [值, 符号]
        if (is_array($val)) {
            $fields_where[] = $field . $val[1] . $bind;
            $info[$bind] = trim($val[0]);
        } else {
            $fields_where[] = $field . '=' . $bind;
            $info[$bind] = trim($val);
        }
    }
    // 获取字段
    $field = $data['field'] ?: '*';
    // 处理连表
    $join = $data['join'] ? ' LEFT JOIN ' . $data['join'] : '';
    // 处理排序
    $order_by = $data['order'] ? ' ORDER BY ' . $data['order'] : '';
    // 处理分组
    $group_by = $data['group'] ? ' GROUP BY ' . $data['group'] : '';
    // 条数
    $limit = $data['limit'] ? ' LIMIT ' . $data['limit'] : '';
    // 执行
    $sth = $pdo->prepare('SELECT ' . $field . ' FROM ' . $table . $join . ' WHERE ' . implode(' AND ', $fields_where) . $group_by . $order_by . $limit);
    $sth->execute($info);
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}

// PDO更新数据
function update_pdo($pdo, $table, $data, $where, $limit = 'LIMIT 1')
{
    // 处理数据字段
    foreach ($data as $field => $val) {
        // 字段自增
        if (preg_match("/" . $field . "\s*\+/i", $val)) {
            $fields_update[] = $field . '=' . $val;
        } else {
            $bind = ':' . $field;
            $fields_update[] = $field . '=' . $bind;
            $info[$bind] = trim($val);
        }
    }
    // 处理条件字段
    foreach ($where as $field => $val) {
        $bind = ':' . $field;
        $fields_where[] = $field . '=' . $bind;
        $info[$bind] = trim($val);
    }
    // 执行
    $sth = $pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $fields_update) . ' WHERE ' . implode(' AND ', $fields_where) . ' ' . $limit);
    if ($sth->execute($info)) {
        output_json_info();
    }
    output_json_error(-2, $sth->errorInfo());
}

// PDO插入数据
function insert_pdo($pdo, $table, $data)
{
    // 处理数据字段
    foreach ($data as $field => $val) {
        $bind = ':' . $field;
        $fields_insert[] = $field;
        $fields_val[] = $bind;
        $info[$bind] = trim($val);
    }
    // 执行
    $sth = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $fields_insert) . ') VALUES (' . implode(',', $fields_val) . ')');
    if ($sth->execute($info)) {
        output_json_info();
    }
    output_json_error(-1, $sth->errorInfo());
}

// PDO连接
function connect_pdo($DB_CONFIG)
{
    $pdo = new PDO('mysql:dbname=' . $DB_CONFIG['db'] . ';host=' . $DB_CONFIG['host'], $DB_CONFIG['user'], $DB_CONFIG['pwd']);
    $pdo->exec("SET NAMES 'utf8';");
    return $pdo;
}

// Json输出信息
function output_json_info($msg = '')
{
    echo json_encode(['status' => 0, 'content' => $msg]);
    exit;
}

// Json输出错误
function output_json_error($status, $msg = '')
{
    echo json_encode(['status' => $status, 'content' => $msg]);
    exit;
}

// 判断时间为空
function check_time_empty($time)
{
    return empty($time) || $time == '0000-00-00 00:00:00';
}

// 格式化时间
function format_date($date, $format = 'Ymd')
{
    if (check_time_empty($date)) return '';
    return date($format, strtotime($date));
}

// 表单添加
// $option, ['name', id', 'title', 'field']
function form_add($option)
{
    // 字段
    foreach ($option['field'] as $field => $sub) {
        $placeholder = $sub['placeholder'] ?: $sub['txt'];
        switch ($sub['type']) {
            case 'select':
                $field_html = <<<EOF
<select name='{$field}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}">
{$sub['option']()}
</select>
EOF;
                break;
            case 'textarea':
                $field_html = <<<EOF
<textarea name='{$field}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}" rows=2></textarea>
EOF;
                break;
            case 'hidden':
                $field_html = <<<EOF
<input type='hidden' name='{$field}' value='{$sub['val']}' />
EOF;
                break;
            default:
                $field_html = <<<EOF
<input type="text" name='{$field}' value='{$sub['val']}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}">
EOF;
                break;
        }
        $form_field .= <<<EOF
<div class="form-group">
    <label for="{$field}_{$option['id']}" class="col-sm-2 control-label">{$sub['txt']}</label>
    <div class="col-sm-10">
        {$field_html}
    </div>
</div>
EOF;
    }
    // 表单
    $form = <<<EOF
<div class="modal fade" id="modal_add_{$option['name']}_{$option['id']}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$option['title']}</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id='form_add_{$option['name']}_{$option['id']}'>
                    {$form_field}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary form_add_{$option['name']}_submit" id="form_add_{$option['name']}_submit" data-id='{$option['id']}'>Save</button>
            </div>
        </div>
    </div>
</div>
EOF;

    return $form;
}

// 表单编辑
// $option, ['name', id', 'title', 'field']
function form_edit($option)
{
    // 字段
    foreach ($option['field'] as $field => $sub) {
        $placeholder = $sub['placeholder'] ?: $sub['txt'];
        switch ($sub['type']) {
            case 'select':
                $field_html = <<<EOF
<select name='{$field}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}">
{$sub['option']()}
</select>
EOF;
                break;
            case 'textarea':
                $field_html = <<<EOF
<textarea name='{$field}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}" rows=2>{$sub['val']}</textarea>
EOF;
                break;
            case 'hidden':
                $field_html = <<<EOF
<input type='hidden' name='{$field}' value='{$sub['val']}' />
EOF;
                break;
            default:
                $field_html = <<<EOF
<input type="text" name='{$field}' value='{$sub['val']}' class="form-control" id="{$field}_{$option['id']}" placeholder="{$placeholder}">
EOF;
                break;
        }
        $form_field .= <<<EOF
<div class="form-group">
    <label for="{$field}_{$option['id']}" class="col-sm-2 control-label">{$sub['txt']}</label>
    <div class="col-sm-10">
        {$field_html}
    </div>
</div>
EOF;
    }
    // 表单
    $form = <<<EOF
<div class="modal fade" id="modal_edit_{$option['name']}_{$option['id']}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$option['title']}</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id='form_edit_{$option['name']}_{$option['id']}'>
                    {$form_field}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary form_edit_{$option['name']}_submit" data-id='{$option['id']}'>Save</button>
            </div>
        </div>
    </div>
</div>
EOF;

    return $form;
}

// Iframe延迟加载
function iframe_lazyload($data)
{
    foreach ($data as $sub) {
        $html .= <<<EOF
<div class="container">
    <div class="row text-center">
        <div class="col-md-12">
            <div id="{$sub['title']}">
                <h3><a href='{$sub['url']}' target='_blank'>{$sub['title']}</a></h3>
                <hr>
                <div class='lazyload' style="{$sub['style']}">
                    <!--
                    <iframe src='{$sub['url']}' scrolling="no" style="{$sub['style']}" {$sub['iframe']}></iframe>
                    -->
                </div>
            </div>
        </div>
    </div>
</div>
EOF;
    }
    return $html;
}

// 表格
function table(&$gdate, $data, $option)
{
    foreach ($option['field'] as $field => $sub) {
        $th .= <<<EOF
<th width='{$sub['width']}'>{$sub['txt']}</th>
EOF;
    }
    foreach ($data as $sub) {
        // 处理数据
        $option['process'] ? $option['process']($gdate, $sub) : '';
        $td = '';
        $tr_attr = $option['tr_attr'] ? $option['tr_attr']($gdate, $sub) : '';
        foreach ($option['field'] as $field => $sub2) {
            $td_attr = $sub2['td_attr'] ? $sub2['td_attr']($gdate, $sub) : ($sub2['title'] ? 'title="' . $sub[$sub2['title']] . '"' : '');
            // 处理逻辑
            $val = $sub2['process'] ? $sub2['process']($gdate, $sub) : ($sub2['func'] ? $sub2['func']($sub[$field]) : $sub[$field]);
            $td .= <<<EOF
<td {$td_attr}>{$val}</td>
EOF;
        }
        $tr .= <<<EOF
<tr {$tr_attr}>
    {$td}
</tr>
EOF;
    }

    // 处理数据
    $option['process_after'] ? $option['process_after']($gdate, $sub) : '';
    $sub_title = $option['sub_title'] ? $option['sub_title']($gdate) : '';
    $html = <<<EOF
<div class="container" id='{$option['id']}'>
    <div class="row">
        <div class="col-md-12">
            <h3 class='text-center'>{$option['title']}</h3>
            {$sub_title}
            <hr>
            <table class="table table-bordered2 table-striped table-hover table-condensed">
                <thead>
                <tr>
                    {$th}
                </tr>
                </thead>
                <tbody>
                    {$tr}
                </tbody>
            </table>
        </div>
    </div>
</div>
EOF;

    return $html;
}

?>

<html>
<head>
    <meta charset="utf-8">
    <title>个人学习网站导航</title>
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link href="https://cdn.bootcss.com/datatables/1.10.15/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
    <script src="//cdn.bootcss.com/bootstrap/3.3.6/js/bootstrap.js"></script>
    <script src="https://cdn.bootcss.com/jquery-lazyload-any/0.3.0/jquery.lazyload-any.min.js"></script>
    <script src="https://cdn.bootcss.com/datatables/1.10.15/js/jquery.dataTables.min.js"></script>
    <style>
        .table th {
            font-size: 10;
        }

        .table td {
            font-size: 9;
        }

        .table-hover > tbody > tr:hover {
            background-color: #e0eee8;
        }

        .nav_tech {
            position: fixed;
            z-index: 100;
            top: 10px;
        }

        .nav_right {
            position: fixed;
            z-index: 100;
            top: 10px;
            right: 1px;
        }

        .nav_tech button,
        .nav_right button {
            display: block;
            margin: 5px 5px;
            width: 85px;
        }

        iframe {
            width: 99%;
            height: 3500px;
            border: 0;
        }
    </style>
</head>

<body>
<div class='nav_tech'>
    <a href='#wetsite'>
        <button class="btn btn-default btn-xs">网站导航</button>
    </a>
    <?php foreach ($website_left_info as $sub) { ?>
        <a href='#<?=$sub["title"];?>' class='click_website_url' data-id='<?=$sub['id'];?>'>
            <button class="btn btn-xs <?=$sub['class'];?>"><?=$sub['title'];?></button>
        </a>
    <?php } ?>
</div>

<!-- 网站导航 -->
<?=table($gdata, $website_info, ['id' => 'wetsite', 'title' => '网站导航', 'sub_title' => function (&$gdata) {
    $html = '<h6 class="text-center">' . date('Y-m-d') . '</h6><div class="text-right"><button class="btn btn-success btn-xs glyphicon glyphicon-plus" data-toggle="modal" data-target="#modal_add_website_">网站</button></div>';
    $html .= form_add(['name' => 'website', 'title' => "添加网站", 'field' => [
        'title' => ['txt' => '*标题'],
        'url' => ['txt' => '*网址'],
        'category_name' => ['txt' => '*大分类'],
        'sub_category_name' => ['txt' => '*子分类'],
        'description' => ['txt' => '描述', 'type' => 'textarea'],
    ]]);
    return $html;
}, 'field' => [
    'id' => ['width' => '5%', 'txt' => 'ID'],
    'category_name' => ['width' => '12%', 'txt' => '分类', 'process' => function ($gdata, $website_item) {
        return $website_item['category_name'] . '-' . $website_item['sub_category_name'];
    }],
    'visit_count' => ['width' => '8%', 'txt' => '访问数', 'td_attr' => function ($gdata, $website_item) {
        return "id='website_visit_count_" . $website_item['id'] . "'";
    }],
    'title' => ['width' => '15%', 'txt' => '标题', 'func' => 'nl2br', 'title' => 'id'],
    'url' => ['width' => '18%', 'txt' => '网址', 'process' => function ($gdata, $website_item) {
        return "<a href='javascript:void(0);' class='click_website' id='website_url_" . $website_item['id'] . "' data-id='" . $website_item['id'] . "' data-url='" . $website_item['url'] . "'>" . $website_item['url'] . "</a>";
    }],
    'description' => ['width' => '15%', 'txt' => '描述', 'func' => 'nl2br'],
    'is_show_menu' => ['width' => '10%', 'txt' => '菜单', 'process' => function ($gdata, $website_item) {
        $html = $website_item['is_show_menu'] ? '<span class="btn btn-primary btn-xs" title="出现在菜单">是</span>' : '<span title="不出现在菜单">否</span>';
        $html .= $website_item['menu_position'] == 1 ? '<span title="左边菜单">左</span>' : '<span title="右边菜单">右</span>';
        $html .= '<span class="btn ' . $website_item['class'] . ' btn-xs" title="排序">' . $website_item['display_order'] . '</span>';
        return $html;
    }],
    'remark' => ['width' => '8%', 'txt' => '备注', 'func' => 'nl2br'],
    '' => ['width' => '6%', 'txt' => '操作', 'process' => function ($gdata, $website_item) {
        $html = "<button class='btn btn-success btn-xs glyphicon glyphicon-pencil' data-toggle='modal' data-target='#modal_edit_website_" . $website_item['id'] . "'></button>";
        $html .= form_edit(['name' => 'website', 'id' => $website_item['id'], 'title' => "编辑网站", 'field' => [
            'title' => ['txt' => '*标题', 'val' => $website_item['title']],
            'url' => ['txt' => '*网址', 'val' => $website_item['url']],
            'category_name' => ['txt' => '*大分类', 'val' => $website_item['category_name']],
            'sub_category_name' => ['txt' => '*子分类', 'val' => $website_item['sub_category_name']],
            'is_show_menu' => ['txt' => '菜单显示', 'type' => 'select', 'option' => function () use ($website_item) {
                foreach ([0 => '否', 1 => '是'] as $val => $txt) $option .= "<option value='{$val}'" . ($website_item['is_show_menu'] == $val ? 'selected' : '') . ">{$txt}</option>";
                return $option;
            }],
            'menu_position' => ['txt' => '菜单位置', 'type' => 'select', 'option' => function () use ($website_item) {
                foreach ([1 => '左边', 2 => '右边'] as $val => $txt) $option .= "<option value='{$val}'" . ($website_item['menu_position'] == $val ? 'selected' : '') . ">{$txt}</option>";
                return $option;
            }],
            'display_order' => ['txt' => '排序', 'val' => $website_item['display_order']],
            'class' => ['txt' => '类', 'val' => $website_item['class']],
            'style' => ['txt' => '样式', 'val' => $website_item['style']],
            'description' => ['txt' => '描述', 'val' => $website_item['description']],
            'remark' => ['txt' => '备注', 'val' => $website_item['remark']],
            'id' => ['type' => 'hidden', 'val' => $website_item['id']],
        ]]);
        return $html;
    }],
    'created_at' => ['width' => '6%', 'txt' => '创建~更新时间', 'td_attr' => function ($gdata, $website_item) {
        return 'title="' . $website_item['created_at'] . '~' . $website_item['updated_at'] . '"';
    }, 'process' => function ($gdata, $website_item) {
        return format_date($website_item['created_at']) . '~' . format_date($website_item['updated_at']);
    }],
]]);?>

<?=iframe_lazyload($website_left_info);?>

<div class='nav_right'>
    <?php foreach ($website_right_info as $sub) { ?>
        <a href='#<?=$sub["title"];?>' class='click_website_url' data-id='<?=$sub['id'];?>'>
            <button class="btn btn-xs <?=$sub['class'];?>"><?=$sub['title'];?></button>
        </a>
    <?php } ?>
</div>
<?=iframe_lazyload($website_right_info);?>

<script>
    // 刷新
    function refresh() {
        document.location.reload();
    }

    // 绑定事件
    function bind_event(obj, option) {
        var event = option.event ? option.event : 'click';
        $(obj).on(event, function () {
            var p = this;
            var attr_val = option.attr ? $(p).attr(option.attr) : $(p).attr('data-id');
            $(p).attr('disabled', true);
            $.ajax({
                type: 'POST',
                url: '<?=$_SERVER["PHP_SELF"];?>?act=' + option.act,
                dataType: 'json',
                data: option.form ? $('#' + option.form + attr_val).serialize() : {id: attr_val},
                success: function (data) {
                    if (data.status != 0) {
                        alert(data.content);
                    } else {
                        option.callback(attr_val);
                    }
                    $(p).attr('disabled', false);
                }
            });
        })
    }

    // 表格排序搜索
    function data_table(obj) {
        $(obj).DataTable({
            "paging": false,
            "order": [],
            "autoWidth": false,
            "language": {
                "search": "",
                "searchPlaceholder": "搜索",
            }
        });
    }

    $(function () {
        // 表格排序
        data_table('.table');
        // 网站导航, 点击网站
        bind_event('.click_website', {
            act: 'click_website', callback: function (attrVal) {
                $('#website_visit_count_' + attrVal).text(parseInt($('#website_visit_count_' + attrVal).text()) + 1);
                window.open($('#website_url_' + attrVal).attr('data-url'));
            }
        });
        // 菜单, 点击网站
        bind_event('.click_website_url', {act: 'click_website'});
        // 网站导航, 添加
        bind_event('#form_add_website_submit', {act: 'add_website', form: 'form_add_website_', callback: refresh});
        // 网站导航, 编辑
        bind_event('.form_edit_website_submit', {act: 'edit_website', form: 'form_edit_website_', callback: refresh});
    });

    // 延迟加载
    $('.lazyload').lazyload();
</script>
</body>
</html>


