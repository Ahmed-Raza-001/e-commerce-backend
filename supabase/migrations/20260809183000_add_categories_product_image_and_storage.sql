-- 1. Create categories table
create table if not exists public.categories (
  id uuid primary key default gen_random_uuid(),
  name varchar(255) not null,
  slug varchar(255) not null unique,
  description text,
  image varchar(255),
  status varchar(50) not null default 'active',
  parent_id uuid references public.categories(id) on delete set null,
  created_at timestamptz not null default now()
);

-- Enable RLS for categories
alter table public.categories enable row level security;

-- Allow public read access to categories
create policy "Categories are publicly readable"
  on public.categories
  for select
  using (true);

-- 2. Update products table with image and category_id
alter table public.products
  add column if not exists image varchar(500),
  add column if not exists category_id uuid references public.categories(id) on delete set null;

-- 3. Create Storage bucket for 'products' if it does not exist
insert into storage.buckets (id, name, public)
values ('products', 'products', true)
on conflict (id) do update set public = true;

-- 4. Storage Policies for 'products' bucket
-- Allow public access to view/download images
create policy "Public Access to Products Bucket"
  on storage.objects for select
  using (bucket_id = 'products');

-- Allow uploads to products bucket
create policy "Allow Uploads to Products Bucket"
  on storage.objects for insert
  with check (bucket_id = 'products');

-- Allow update/upsert in products bucket
create policy "Allow Updates to Products Bucket"
  on storage.objects for update
  using (bucket_id = 'products');

-- Allow delete from products bucket
create policy "Allow Deletes from Products Bucket"
  on storage.objects for delete
  using (bucket_id = 'products');
