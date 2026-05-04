<?php declare(strict_types = 0);

namespace Modules\ZabbixStatusList\Includes;

use Zabbix\Widgets\{CWidgetField, CWidgetForm};
use Zabbix\Widgets\Fields\{CWidgetFieldMultiSelectItem, CWidgetFieldTimePeriod, CWidgetFieldTextArea, CWidgetFieldTextBox, CWidgetFieldCheckBox};
use CWidgetsData;

class WidgetForm extends CWidgetForm {

    public function addFields(): self {
        return $this
            ->addField(
            	 new CWidgetFieldTextArea('custom_names_json')
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_value', 'Show "Value" column'))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_status', 'Show "Status" column'))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_item', 'Show "Item" column'))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldMultiSelectItem('itemids', 'Items'))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldTimePeriod('time_period', 'Time period'))
                    ->setDefault([
                        CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
                            CWidgetField::REFERENCE_DASHBOARD, CWidgetsData::DATA_TYPE_TIME_PERIOD
                        )
                    ])
                    ->setDefaultPeriod(['from' => 'now-1h', 'to' => 'now'])
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            );
    }
}
