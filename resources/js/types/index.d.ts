import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    auth: Auth;
    flash: { success?: string | null; error?: string | null };
    googleEnabled: boolean;
    config: { books: { advertised_book_id: number | string } };
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    coins: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface BookChapter {
    title: string;
    page: number;
}

export interface BookSummary {
    id: number;
    title: string;
    description: string;
    total_pages: number;
    price_per_page: number;
    cover: string | null;
    chapters: BookChapter[];
    illustrations: { image: string; page: number }[];
    free_pages: number;
    registered_free_pages: number;
    owned_pages: number;
}

export interface ReaderPage {
    id: number;
    page_number: number;
    chapter: string | null;
    content: string | null;
    audio_url: string | null;
    image: string | null;
    video: string | null;
    locked: 'login' | 'coins' | null;
    scene: string | null;
}
