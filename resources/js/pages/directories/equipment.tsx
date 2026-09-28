import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface EquipmentTypeItem {
    id: number;
    name: string;
    users_count: number;
    /** Which drawing stands for the category; null is the plain box. */
    icon: string | null;
}

/** Categories of hardware; the units themselves live in the equipment section. */
export default function Equipment({ items, icons }: { items: EquipmentTypeItem[]; icons: string[] }) {
    return (
        <DirectoriesLayout title="Категории техники">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count, icon: item.icon }))}
                icons={icons}
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
