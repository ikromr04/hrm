import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface EquipmentTypeItem {
    id: number;
    name: string;
    users_count: number;
}

/** Categories of hardware; the units themselves live in the equipment section. */
export default function Equipment({ items }: { items: EquipmentTypeItem[] }) {
    return (
        <DirectoriesLayout title="Категории техники">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count }))}
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
