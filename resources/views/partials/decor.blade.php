{{-- Elemen dekoratif hijau di belakang foto (pimpinan & barisan waka).
     Semua bentuk absolute + berbasis persentase, jadi menyesuaikan container
     besar (pimpinan) maupun kecil (barisan waka). Lingkaran pakai aspect-square
     agar tetap bulat di rasio container apa pun. $style default 'arch'. --}}
@php $style = $style ?? 'arch'; @endphp

@switch($style)
    @case('circles')
        <span aria-hidden="true" class="absolute right-[8%] top-[2%] w-[24%] aspect-square rounded-full bg-primary/20"></span>
        <span aria-hidden="true" class="absolute left-[4%] top-[20%] w-[10%] aspect-square rounded-full bg-primary"></span>
        <span aria-hidden="true" class="absolute bottom-[10%] left-[8%] w-[15%] aspect-square rounded-full bg-primary/70"></span>
        <span aria-hidden="true" class="absolute bottom-0 right-[4%] w-[18%] aspect-square rounded-full bg-primary/30"></span>
        @break

    @case('blob')
        <span aria-hidden="true" class="absolute left-1/2 top-1/2 w-[82%] aspect-square -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/15 blur-2xl"></span>
        @break

    @case('pill')
        <span aria-hidden="true" class="absolute bottom-0 left-1/2 h-[92%] w-[56%] -translate-x-1/2 rounded-full bg-primary"></span>
        <span aria-hidden="true" class="absolute right-[8%] top-[10%] w-[14%] aspect-square rounded-full bg-primary/30"></span>
        @break

    @case('diagonal')
        <span aria-hidden="true" class="absolute bottom-[6%] left-1/2 h-[68%] w-[68%] -translate-x-1/2 -rotate-6 rounded-[24%] bg-primary/25"></span>
        <span aria-hidden="true" class="absolute bottom-[2%] left-1/2 h-[70%] w-[70%] -translate-x-1/2 rotate-12 rounded-[24%] bg-primary"></span>
        @break

    @case('rings')
        <span aria-hidden="true" class="absolute right-[6%] top-[6%] w-[26%] aspect-square rounded-full border-[6px] border-primary/40"></span>
        <span aria-hidden="true" class="absolute bottom-[8%] left-[4%] w-[18%] aspect-square rounded-full border-[5px] border-primary/60"></span>
        <span aria-hidden="true" class="absolute bottom-0 left-1/2 h-[64%] w-[60%] -translate-x-1/2 rounded-t-[45%] rounded-b-[14%] bg-primary/80"></span>
        @break

    @case('none')
        @break

    @default {{-- arch --}}
        <span aria-hidden="true" class="absolute bottom-0 left-1/2 h-[86%] w-[74%] -translate-x-1/2 rounded-t-[46%] rounded-b-[14%] bg-primary"></span>
        <span aria-hidden="true" class="absolute left-[3%] top-[12%] w-[13%] aspect-square rounded-full bg-primary/70"></span>
        <span aria-hidden="true" class="absolute right-[6%] top-[26%] w-[18%] aspect-square rounded-full bg-primary/20"></span>
        <span aria-hidden="true" class="absolute bottom-[16%] left-[1%] w-[8%] aspect-square rounded-full bg-primary"></span>
@endswitch
