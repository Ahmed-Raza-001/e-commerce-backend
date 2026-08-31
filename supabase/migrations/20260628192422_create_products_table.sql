create table public.products (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  description text,
  price numeric(10,2) not null check (price >= 0),
  stock integer not null default 0 check (stock >= 0),
  created_at timestamptz not null default now()
);

alter table public.products enable row level security;

-- Allow anyone to read products
create policy "Products are publicly readable"
  on public.products
  for select
  using (true);
