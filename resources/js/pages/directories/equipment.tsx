import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';
import { type CategoryField, type FieldTypeOption } from '@/lib/equipment-fields';

interface EquipmentTypeItem {
    id: number;
    name: string;
    users_count: number;
    /** Which drawing stands for the category; null is the plain box. */
    icon: string | null;
    /** Whether a unit of this category comes with anything at all. */
    has_accessories: boolean;
    /** What units of this category are described by. */
    fields: CategoryField[];
}

/** Categories of hardware; the units themselves live in the equipment section. */
export default function Equipment({
    items,
    icons,
    fieldTypes,
    defaultFields,
    canEdit,
}: {
    items: EquipmentTypeItem[];
    icons: string[];
    fieldTypes: FieldTypeOption[];
    /** What a new category starts off with, so nobody types these out again. */
    defaultFields: CategoryField[];
    /** Whether this list of categories is this person's to change. */
    canEdit: boolean;
}) {
    return (
        <DirectoriesLayout title="Категории техники">
            <DirectoryManager
                items={items.map((item) => ({ ...item, label: item.name }))}
                canEdit={canEdit}
                icons={icons}
                // A monitor has a diagonal and no processor: what the units of a
                // category are described by is decided here, category by category.
                fieldTypes={fieldTypes}
                defaultFields={defaultFields}
                field="name"
                route="directories.equipment"
                labels={{
                    add: 'Добавить категорию',
                    create: 'Новая категория техники',
                    edit: 'Изменить категорию',
                    accusative: 'категорию',
                }}
                countLabel="Единиц"
            />
        </DirectoriesLayout>
    );
}
