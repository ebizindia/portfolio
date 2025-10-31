<?php
namespace eBizIndia;

class Categorylist {
    public static function getList($options = []) {
        $data = [];
        $fields_mapper = [];

        $fields_mapper['*'] = "cm.id as id, cm.cat_name as cat_name, cm.annual_price as annual_price, cm.lifetime_price as lifetime_price";
        $fields_mapper['recordcount'] = 'count(distinct(cm.id))';
        $fields_mapper['id'] = "cm.id";
        $fields_mapper['cat_name'] = "cm.cat_name";
        $fields_mapper['annual_price'] = "cm.annual_price";
        $fields_mapper['lifetime_price'] = "cm.lifetime_price";

        $where_clause = [];
        $str_params_to_bind = [];
        $int_params_to_bind = [];

        if (array_key_exists('filters', $options) && is_array($options['filters'])) {
            $field_counter = 0;
            foreach ($options['filters'] as $filter) {
                ++$field_counter;
                switch ($filter['field']) {
                    case 'id':
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $id) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_id_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_id_{$k}_"] = $id;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;

                            case 'NOT_IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $id) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_id_{$k}_";
                                        $int_params_to_bind[":whr" . $field_counter . "_id_{$k}_"] = $id;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' not in(' . implode(',', $place_holders) . ') ';
                                }
                                break;

                            case 'NOT_EQUAL':
                                $id = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_id';
                                $int_params_to_bind[':whr' . $field_counter . '_id'] = $id;
                                break;

                            default:
                                $id = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_id';
                                $int_params_to_bind[':whr' . $field_counter . '_id'] = $id;
                        }
                        break;

                    case 'cat_name':
                        switch ($filter['type']) {
                            case 'IN':
                                if (is_array($filter['value'])) {
                                    $place_holders = [];
                                    $k = 0;
                                    foreach ($filter['value'] as $cat_name) {
                                        $k++;
                                        $place_holders[] = ":whr" . $field_counter . "_cat_name_{$k}_";
                                        $str_params_to_bind[":whr" . $field_counter . "_cat_name_{$k}_"] = $cat_name;
                                    }
                                    $where_clause[] = $fields_mapper[$filter['field']] . ' in(' . implode(',', $place_holders) . ') ';
                                }
                                break;

                            case 'CONTAINS':
                                $cat_name = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . ' like :whr' . $field_counter . '_cat_name';
                                $str_params_to_bind[':whr' . $field_counter . '_cat_name'] = '%' . $cat_name . '%';
                                break;

                            case 'NOT_EQUAL':
                                $cat_name = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_cat_name';
                                $str_params_to_bind[':whr' . $field_counter . '_cat_name'] = $cat_name;
                                break;

                            default:
                                $cat_name = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_cat_name';
                                $str_params_to_bind[':whr' . $field_counter . '_cat_name'] = $cat_name;
                        }
                        break;

                    case 'annual_price':
                    case 'lifetime_price':
                        $price = (is_array($filter['value'])) ? $filter['value'][0] : $filter['value'];
                        switch ($filter['type']) {
                            case 'NOT_EQUAL':
                                $where_clause[] = $fields_mapper[$filter['field']] . '!=:whr' . $field_counter . '_price';
                                $int_params_to_bind[':whr' . $field_counter . '_price'] = $price;
                                break;

                            default:
                                $where_clause[] = $fields_mapper[$filter['field']] . '=:whr' . $field_counter . '_price';
                                $int_params_to_bind[':whr' . $field_counter . '_price'] = $price;
                        }
                        break;
                }
            }
        }

        $select_string = $fields_mapper['*'];

        if (array_key_exists('fieldstofetch', $options) && is_array($options['fieldstofetch'])) {
            $fields_to_fetch_count = count($options['fieldstofetch']);
            if ($fields_to_fetch_count > 0) {
                $selected_fields = [];
                if (in_array('recordcount', $options['fieldstofetch'])) {
                    $record_count = true;
                } else {
                    if (!in_array('*', $options['fieldstofetch'])) {
                        if (!in_array('id', $options['fieldstofetch'])) {
                            $options['fieldstofetch'][] = 'id';
                            $fields_to_fetch_count += 1;
                        }
                    }
                }

                for ($i = 0; $i < $fields_to_fetch_count; $i++) {
                    if (array_key_exists($options['fieldstofetch'][$i], $fields_mapper)) {
                        $selected_fields[] = $fields_mapper[$options['fieldstofetch'][$i]] . (($options['fieldstofetch'][$i] != '*') ? ' as ' . $options['fieldstofetch'][$i] : '');
                    }
                }

                if (count($selected_fields) > 0) {
                    $select_string = implode(', ', $selected_fields);
                }
            }
        }

        $select_string = ($record_count) ? $select_string : 'distinct ' . $select_string;
        $group_by_clause = '';
        if (array_key_exists('group_by', $options) && is_array($options['group_by'])) {
            foreach ($options['group_by'] as $field) {
                if (preg_match("/^(cm)\./", $fields_mapper[$field])) {
                    $group_by_clause .= ", " . $fields_mapper[$field];
                } else {
                    $group_by_clause .= ", $field";
                }
            }

            $group_by_clause = trim($group_by_clause, ",");
            if ($group_by_clause != '') {
                $group_by_clause = ' GROUP BY ' . $group_by_clause;
            }
        }

        $order_by_clause = $order_by_clause_outer = '';
        if (array_key_exists('order_by', $options) && is_array($options['order_by'])) {
            foreach ($options['order_by'] as $order) {
                if (preg_match("/^(cm)\./", $fields_mapper[$order['field']])) {
                    $order_by_clause .= ", " . $fields_mapper[$order['field']];
                    if (!$record_count) {
                        if (!preg_match("/,?\s*" . str_replace('.', "\.", $fields_mapper[$order['field']]) . "/", $select_string)) {
                            $select_string .= ", " . $fields_mapper[$order['field']] . ' as ' . $order['field'];
                        }
                    }
                } else if (array_key_exists($order['field'], $fields_mapper)) {
                    if (!preg_match("/\s*as\s*" . $order['field'] . "/", $select_string)) {
                        $select_string .= ", " . $fields_mapper[$order['field']] . ' as ' . $order['field'];
                    }
                    $order_by_clause .= ", " . $order['field'];
                }

                if (array_key_exists('type', $order) && $order['type'] == 'DESC') {
                    $order_by_clause .= ' DESC';
                }
            }

            $order_by_clause = trim($order_by_clause, ",");
            if ($order_by_clause != '') {
                $order_by_clause = ' ORDER BY ' . $order_by_clause;
            }

            if ($order_by_clause != '' && !stristr($order_by_clause, 'cm.id')) {
                $order_by_clause .= ', ' . $fields_mapper['id'] . ' DESC ';
            }
        }

        if (!$record_count && $order_by_clause == '') {
            $order_by_clause = " ORDER BY cm.cat_name ASC";
            if (!preg_match("/\s+as\s+cat_name/", $select_string)) {
                $select_string .= ', ' . $fields_mapper['cat_name'] . ' as cat_name';
            }
        }

        $limit_clause = '';
        if (array_key_exists('page', $options) && filter_var($options['page'], FILTER_VALIDATE_INT) && $options['page'] > 0 && array_key_exists('recs_per_page', $options) && filter_var($options['recs_per_page'], FILTER_VALIDATE_INT) && $options['recs_per_page'] > 0) {
            $limit_clause = "LIMIT " . (($options['page'] - 1) * $options['recs_per_page']) . ", $options[recs_per_page]";
        }

        $where_clause_string = '';
        if (!empty($where_clause)) {
            $where_clause_string = ' WHERE ' . implode(' AND ', $where_clause);
        }

        $sql = "SELECT $select_string from `category_mast` as cm $where_clause_string $group_by_clause $order_by_clause $limit_clause";

        $error_details_to_log = [];
        $error_details_to_log['function'] = __METHOD__;
        $error_details_to_log['sql'] = $sql;
        $error_details_to_log['str_params_to_bind'] = $str_params_to_bind;
        $error_details_to_log['int_params_to_bind'] = $int_params_to_bind;

        try {
            $pdo_stmt_obj = PDOConn::query($sql, $str_params_to_bind, $int_params_to_bind);
            if (array_key_exists('resourceonly', $options) && $options['resourceonly']) {
                return $pdo_stmt_obj;
            }

            $data = [];
            while ($row = $pdo_stmt_obj->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            return $data;
        } catch (\Exception $e) {
            if (!is_a($e, '\PDOStatement')) {
                ErrorHandler::logError($error_details_to_log, $e);
            } else {
                ErrorHandler::logError($error_details_to_log);
            }
            return false;
        }
    }
}
