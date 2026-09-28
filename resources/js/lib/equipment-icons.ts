import {
    Armchair,
    Battery,
    Cable,
    Camera,
    HardDrive,
    Headphones,
    Keyboard,
    Laptop,
    Monitor,
    Mouse,
    Package,
    Printer,
    Projector,
    Router,
    Scan,
    Server,
    Smartphone,
    Speaker,
    Tablet,
    Watch,
    type LucideIcon,
} from 'lucide-react';

/**
 * The drawings a category of hardware can be shown by, keyed as the category
 * stores them. The list is kept beside the server's own (AppSupportEquipmentIcons)
 * and has to hold the same keys: the directory offers what the server accepts,
 * and the interface draws what it stored.
 */
export const equipmentIcons: Record<string, LucideIcon> = {
    laptop: Laptop,
    monitor: Monitor,
    smartphone: Smartphone,
    tablet: Tablet,
    keyboard: Keyboard,
    mouse: Mouse,
    headphones: Headphones,
    printer: Printer,
    scanner: Scan,
    projector: Projector,
    camera: Camera,
    server: Server,
    harddrive: HardDrive,
    router: Router,
    speaker: Speaker,
    watch: Watch,
    cable: Cable,
    battery: Battery,
    chair: Armchair,
    package: Package,
};

/** What a category without a drawing of its own is shown by. */
export const fallbackIcon = Package;
