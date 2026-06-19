export interface EventListItem {
    id: string;
    title: string;
    description: string | null;
    type: string;
    status: string;
    starts_at: number | null;
    ends_at: number | null;
    timezone: string;
    venue: string | null;
    location: string;
    city: string;
    country: string;
    latitude: number | null;
    longitude: number | null;
    price: number | null;
    currency: string;
    attendees_count: number;
    image: string;
    images: string[];
}

export interface EventDetail extends EventListItem {
    capacity: number | null;
    organizer: string | null;
    tags: string[];
}

export interface City {
    key: string;
    city: string;
    country: string;
    label: string;
    lat: number;
    lng: number;
}

export interface FilterOptions {
    cities: City[];
    types: string[];
    statuses: string[];
}

export interface EventFilters {
    q: string;
    city: string;
    from: string;
    to: string;
    type: string;
    status: string;
    sort: 'soonest' | 'latest';
}

export interface EventFeedResponse {
    data: EventListItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
