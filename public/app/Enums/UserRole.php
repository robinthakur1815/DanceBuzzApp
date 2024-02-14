<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserRole extends Enum
{
    const SuperAdmin = 50;
    const Author = 51;
    const BlogAuthor = 52;
    const NewsAuthor = 53;
    const CaseStudyAuthor = 54;
    const TestimonialsAuthor = 55;
    const EventsAuthor = 56;
    const StartupAuthor = 57;
    const WorkshopAuthor = 58;
    const ClassesAuthor = 59;
    const CareerAuthor = 60;
    const PeopleAuthor = 61;
    const ServicesAuthor = 62;
    const PanellistAuthor = 63;
    const PartnerGalleryAuthor = 64;
    const AwardsAuthor = 65;
    const CarnivalActivitiesAuthor = 66;
    const CampaignAuthor = 67;
    const Approver = 68;
    const Marketing = 69;
    const ProductAuthor = 70;
}
