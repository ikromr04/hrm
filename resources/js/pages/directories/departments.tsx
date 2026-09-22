import { DirectoryManager } from '@/components/directory-manager';
import DirectoriesLayout from '@/layouts/directories-layout';

interface DepartmentItem {
    id: number;
    name: string;
    parent_id: number | null;
    users_count: number;
}

export default function Departments({ items }: { items: DepartmentItem[] }) {
    return (
        <DirectoriesLayout title="Отделы">
            <DirectoryManager
                items={items.map((item) => ({ id: item.id, label: item.name, users_count: item.users_count, parent_id: item.parent_id }))}
                field="name"
                route="directories.departments"
                labels={{ add: 'Добавить отдел', create: 'Новый отдел', edit: 'Изменить отдел', accusative: 'отдел' }}
                employeesUrl={(item) => route('employees.index', { department: [item.id] })}
                tree
            />
        </DirectoriesLayout>
    );
}
