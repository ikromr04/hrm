import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface EquipmentTypeItem {
    id: number;
    name: string;
    users_count: number;
}

/** Kinds of hardware; the units themselves are filled in on the employee. */
export default function Equipment({ items }: { items: EquipmentTypeItem[] }) {
    return (
        <DirectoriesLayout title="Оборудование">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count }))}
                field="name"
                route="directories.equipment"
                labels={{
                    add: 'Добавить оборудование',
                    create: 'Новый вид оборудования',
                    edit: 'Изменить вид оборудования',
                    accusative: 'вид оборудования',
                }}
            />
        </DirectoriesLayout>
    );
}
